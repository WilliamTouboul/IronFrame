<?php
/**
 * Brique 6 — Écran d'administration des options globales.
 *
 * Le formulaire n'utilise pas la Settings API : elle passe par `options.php`,
 * verrouillé sur `manage_options`, capacité que le client n'a pas et ne doit
 * pas avoir. On poste donc vers `admin-post.php` avec exactement les mêmes
 * gardes que la sauvegarde des meta boxes — nonce, capacité, nettoyage typé.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

/** Identifiant de la page d'administration. */
define('IRON_OPTIONS_PAGE', 'iron-options');

if (!function_exists('iron_register_options_page')) {
    /**
     * @return void
     */
    function iron_register_options_page()
    {
        if (!iron_get_options_schema()) {
            return;
        }

        add_menu_page(
            __('Réglages du site', 'ironframe'),
            __('Réglages du site', 'ironframe'),
            IRON_CAP_EDIT_OPTIONS,
            IRON_OPTIONS_PAGE,
            'iron_render_options_page',
            'dashicons-admin-site-alt3',
            30
        );
    }
}
add_action('admin_menu', 'iron_register_options_page');

if (!function_exists('iron_options_page_assets')) {
    /**
     * @param string $hook
     * @return void
     */
    function iron_options_page_assets($hook)
    {
        if ('toplevel_page_' . IRON_OPTIONS_PAGE !== $hook) {
            return;
        }

        iron_enqueue_field_editor_assets();
    }
}
add_action('admin_enqueue_scripts', 'iron_options_page_assets');

if (!function_exists('iron_render_options_page')) {
    /**
     * Une section par groupe, exactement comme une meta box par groupe sur
     * l'écran d'édition d'une page. Le client retrouve la même mécanique.
     *
     * @return void
     */
    function iron_render_options_page()
    {
        if (!current_user_can(IRON_CAP_EDIT_OPTIONS)) {
            wp_die(esc_html__('Vous n\'avez pas les droits nécessaires.', 'ironframe'));
        }
        ?>
        <div class="wrap iron-options">

            <h1><?php esc_html_e('Réglages du site', 'ironframe'); ?></h1>

            <p class="description">
                <?php esc_html_e('Ces informations sont utilisées partout sur le site. Les modifier ici les met à jour sur toutes les pages.', 'ironframe'); ?>
            </p>

            <?php
            $errors = iron_take_errors(IRON_OPTIONS_PAGE);

            iron_render_error_notice(
                $errors,
                __('Ces champs sont obligatoires et n\'ont pas été enregistrés vides. Leur valeur précédente a été conservée.', 'ironframe')
            );
            ?>

            <?php if (isset($_GET['iron-updated']) && !$errors) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Réglages enregistrés.', 'ironframe'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

                <input type="hidden" name="action" value="iron_save_options">
                <?php wp_nonce_field('iron_save_options'); ?>

                <?php foreach (iron_get_options_schema() as $group) : ?>
                    <div class="iron-options__group postbox">

                        <h2 class="hndle"><span><?php echo esc_html($group['label']); ?></span></h2>

                        <div class="inside">
                            <?php
                            if (!empty($group['toggle'])) {
                                iron_render_group_toggle($group, iron_option_is_enabled($group['key']));
                            }
                            ?>
                            <div class="iron-fields">
                                <?php
                                foreach ($group['fields'] as $field) {
                                    iron_render_option_field($field);
                                }
                                ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>

                <?php submit_button(__('Enregistrer les réglages', 'ironframe')); ?>

            </form>

        </div>
        <?php
    }
}

if (!function_exists('iron_render_option_field')) {
    /**
     * Enveloppe d'un champ, identique à celle des meta boxes.
     *
     * @param array $field
     * @return void
     */
    function iron_render_option_field(array $field)
    {
        $type = iron_field_type($field['type']);

        if (!$type || !is_callable($type['render'])) {
            return;
        }

        ?>
        <div class="iron-field iron-field--<?php echo esc_attr($field['type']); ?>">

            <?php iron_render_field_label($field); ?>

            <div class="iron-field__control">
                <?php call_user_func($type['render'], $field, iron_get_raw_option($field)); ?>
            </div>

            <?php if ('' !== $field['desc']) : ?>
                <p class="iron-field__desc description"><?php echo esc_html($field['desc']); ?></p>
            <?php endif; ?>

        </div>
        <?php
    }
}

if (!function_exists('iron_handle_options_save')) {
    /**
     * Enregistre les options soumises.
     *
     * Comme pour les pages, un champ absent de la soumission n'est jamais
     * effacé : seule une valeur explicitement vide vide le champ.
     *
     * @return void
     */
    function iron_handle_options_save()
    {
        if (!current_user_can(IRON_CAP_EDIT_OPTIONS)) {
            wp_die(esc_html__('Vous n\'avez pas les droits nécessaires.', 'ironframe'));
        }

        check_admin_referer('iron_save_options');

        $submitted = _iron_submitted_values();
        $toggles   = _iron_submitted_toggles();
        $schema    = iron_get_options_schema();

        foreach ($toggles as $group_key => $enabled) {
            if (isset($schema[$group_key]) && !empty($schema[$group_key]['toggle'])) {
                iron_save_option_group_toggle($group_key, $enabled);
            }
        }

        $errors = iron_collect_required_errors($schema, $submitted, $toggles);

        foreach (iron_flatten_schema($schema) as $field) {

            $group = $field['group'];
            $key   = $field['key'];

            if (!isset($submitted[$group]) || !is_array($submitted[$group])) {
                continue;
            }

            if (!array_key_exists($key, $submitted[$group])) {
                continue;
            }

            // Même règle que pour les pages : un champ obligatoire soumis vide
            // n'écrase pas une valeur existante.
            if (isset($errors[$field['path']]) && iron_value_is_filled(iron_get_raw_option($field), $field)) {
                continue;
            }

            iron_save_raw_option($field, $submitted[$group][$key]);
        }

        iron_store_errors(IRON_OPTIONS_PAGE, $errors);

        wp_safe_redirect(add_query_arg(
            ['page' => IRON_OPTIONS_PAGE, 'iron-updated' => '1'],
            admin_url('admin.php')
        ));

        exit;
    }
}
add_action('admin_post_iron_save_options', 'iron_handle_options_save');
