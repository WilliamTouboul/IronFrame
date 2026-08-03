<?php
/**
 * Brique 2 — Assets de l'écran d'édition.
 *
 * Chargés uniquement sur un écran d'édition de page qui possède réellement un
 * schéma : inutile d'imposer la médiathèque JS à des écrans qui n'en ont pas
 * l'usage.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_admin_enqueue_field_assets')) {
    /**
     * @param string $hook
     * @return void
     */
    function iron_admin_enqueue_field_assets($hook)
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        $post = get_post();

        if (!$post || 'page' !== $post->post_type) {
            return;
        }

        if (!iron_get_post_schema($post)) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'iron-admin-fields',
            IRON_URI . '/assets/admin/fields.css',
            [],
            iron_asset_version('assets/admin/fields.css')
        );

        wp_enqueue_script(
            'iron-admin-fields',
            IRON_URI . '/assets/admin/fields.js',
            [],
            iron_asset_version('assets/admin/fields.js'),
            true
        );

        wp_localize_script('iron-admin-fields', 'ironFieldsL10n', [
            'frameTitle'  => __('Choisir une image', 'ironframe'),
            'frameButton' => __('Utiliser cette image', 'ironframe'),
        ]);
    }
}
add_action('admin_enqueue_scripts', 'iron_admin_enqueue_field_assets');
