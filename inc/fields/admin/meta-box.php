<?php
/**
 * Brique 2 — Meta boxes générées à partir du schéma.
 *
 * Un groupe du schéma = une meta box. L'écran d'édition reproduit ainsi les
 * sections de la page, ce qui rend l'interface lisible pour un client qui ne
 * connaît pas WordPress.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_register_meta_boxes')) {
    /**
     * Déclare une meta box par groupe du schéma de la page en cours.
     *
     * @param string  $post_type
     * @param WP_Post $post
     * @return void
     */
    function iron_register_meta_boxes($post_type, $post)
    {
        if ('page' !== $post_type) {
            return;
        }

        foreach (iron_get_post_schema($post) as $group) {
            add_meta_box(
                'iron_group_' . $group['key'],
                $group['label'],
                'iron_render_meta_box',
                $post_type,
                'normal',
                'high',
                ['group' => $group]
            );
        }
    }
}
add_action('add_meta_boxes', 'iron_register_meta_boxes', 10, 2);

if (!function_exists('iron_render_meta_box')) {
    /**
     * Rend une meta box : l'enveloppe de chaque champ est commune, seul le
     * contrôle est délégué au type.
     *
     * @param WP_Post $post
     * @param array   $box
     * @return void
     */
    function iron_render_meta_box($post, $box)
    {
        $group = $box['args']['group'];

        wp_nonce_field('iron_save_fields_' . $post->ID, 'iron_fields_nonce');

        echo '<div class="iron-fields">';

        foreach ($group['fields'] as $field) {

            $type = iron_field_type($field['type']);

            if (!$type || !is_callable($type['render'])) {
                continue;
            }

            $label_for = !isset($type['label_for']) || false !== $type['label_for'];
            ?>
            <div class="iron-field iron-field--<?php echo esc_attr($field['type']); ?>">

                <?php if ($label_for) : ?>
                    <label class="iron-field__label" for="<?php echo esc_attr(iron_field_input_id($field)); ?>">
                        <?php echo esc_html($field['label']); ?>
                    </label>
                <?php else : ?>
                    <span class="iron-field__label"><?php echo esc_html($field['label']); ?></span>
                <?php endif; ?>

                <div class="iron-field__control">
                    <?php call_user_func($type['render'], $field, iron_get_raw_value($field, $post)); ?>
                </div>

                <?php if ('' !== $field['desc']) : ?>
                    <p class="iron-field__desc description"><?php echo esc_html($field['desc']); ?></p>
                <?php endif; ?>

            </div>
            <?php
        }

        echo '</div>';
    }
}

if (!function_exists('iron_save_fields')) {
    /**
     * Enregistre les champs soumis.
     *
     * Le schéma qui fait autorité est celui du template ENREGISTRÉ :
     * `wp_insert_post()` écrit `_wp_page_template` avant de déclencher
     * `save_post`. Si le client vient de changer de template, on n'écrit donc
     * que les champs du nouveau, et les valeurs soumises pour l'ancien sont
     * ignorées plutôt que recopiées sous de mauvaises clés.
     *
     * Un champ absent de la soumission n'est jamais effacé : il peut manquer
     * parce que sa meta box était masquée, pas parce que le client l'a vidé.
     * Vider un champ passe par une valeur vide, qui elle est bien présente.
     *
     * @param int     $post_id
     * @param WP_Post $post
     * @return void
     */
    function iron_save_fields($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (!isset($_POST['iron_fields_nonce'])) {
            return;
        }

        $nonce = sanitize_key(wp_unslash($_POST['iron_fields_nonce']));

        if (!wp_verify_nonce($nonce, 'iron_save_fields_' . $post_id)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $submitted = isset($_POST['iron']) && is_array($_POST['iron'])
            ? wp_unslash($_POST['iron'])
            : [];

        foreach (iron_flatten_schema(iron_get_post_schema($post)) as $field) {

            $group = $field['group'];
            $key   = $field['key'];

            if (!isset($submitted[$group]) || !is_array($submitted[$group])) {
                continue;
            }

            if (!array_key_exists($key, $submitted[$group])) {
                continue;
            }

            iron_save_raw_value($field, $submitted[$group][$key], $post_id);
        }
    }
}
add_action('save_post_page', 'iron_save_fields', 10, 2);
