<?php
/**
 * Colonne « Template » sur la liste des pages.
 *
 * Sur un site de quinze pages, savoir d'un coup d'œil laquelle utilise quel
 * template évite d'ouvrir chaque écran d'édition. Le template déterminant les
 * champs disponibles, c'est aussi la première chose à vérifier quand un champ
 * n'apparaît pas.
 *
 * Masquée au client : elle ne lui apprendrait rien et il ne peut pas changer
 * de template.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_add_template_column')) {
    /**
     * @param array $columns
     * @return array
     */
    function iron_add_template_column($columns)
    {
        if (iron_user_is_client()) {
            return $columns;
        }

        $inserted = [];

        foreach ($columns as $key => $label) {

            // Avant la date, qui est toujours la dernière colonne utile.
            if ('date' === $key) {
                $inserted['iron_template'] = __('Template', 'ironframe');
            }

            $inserted[$key] = $label;
        }

        if (!isset($inserted['iron_template'])) {
            $inserted['iron_template'] = __('Template', 'ironframe');
        }

        return $inserted;
    }
}
add_filter('manage_page_posts_columns', 'iron_add_template_column');

if (!function_exists('iron_render_template_column')) {
    /**
     * @param string $column
     * @param int    $post_id
     * @return void
     */
    function iron_render_template_column($column, $post_id)
    {
        if ('iron_template' !== $column) {
            return;
        }

        $slug = get_page_template_slug($post_id);

        if ('' === $slug || 'default' === $slug) {
            echo '<span aria-hidden="true">—</span>';

            return;
        }

        $templates = wp_get_theme()->get_page_templates(get_post($post_id));
        $name      = isset($templates[$slug]) ? $templates[$slug] : '';

        if ('' === $name) {
            printf(
                '<span class="iron-template-missing">%s</span>',
                esc_html(sprintf(__('%s (introuvable)', 'ironframe'), $slug))
            );

            return;
        }

        $fields = iron_flatten_schema(iron_get_post_schema($post_id));

        printf(
            '%s<br><span class="iron-template-meta">%s</span>',
            esc_html($name),
            esc_html(sprintf(_n('%d champ', '%d champs', count($fields), 'ironframe'), count($fields)))
        );
    }
}
add_action('manage_page_posts_custom_column', 'iron_render_template_column', 10, 2);

if (!function_exists('iron_template_column_style')) {
    /**
     * @return void
     */
    function iron_template_column_style()
    {
        $screen = get_current_screen();

        if (!$screen || 'edit-page' !== $screen->id || iron_user_is_client()) {
            return;
        }
        ?>
        <style>
            .column-iron_template { width: 16%; }
            .iron-template-meta { color: #646970; font-size: 12px; }
            .iron-template-missing { color: #d63638; }
        </style>
        <?php
    }
}
add_action('admin_head', 'iron_template_column_style');
