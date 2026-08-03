<?php
/**
 * Configuration générale du thème.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_theme_setup')) {
    /**
     * Déclare les supports du thème et les tailles d'images.
     *
     * Tout passe par `after_setup_theme` : appeler `add_theme_support()` à la
     * racine de functions.php fonctionne par accident, pas par contrat.
     */
    function iron_theme_setup()
    {
        load_theme_textdomain('ironframe', IRON_PATH . '/languages');

        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo');
        add_theme_support('align-wide');
        add_theme_support('responsive-embeds');
        add_theme_support('html5', [
            'search-form',
            'gallery',
            'caption',
            'style',
            'script',
        ]);

        add_image_size('4_3', 1024, 768, true);
        add_image_size('16_9', 1280, 720, true);
        add_image_size('1_1', 1024, 1024, true);
        add_image_size('hero_inner_hd', 1920, 600, true);
        add_image_size('600x400', 600, 400, false);
        add_image_size('400x400', 400, 400, true);
        add_image_size('400x300', 400, 300, true);
    }
}
add_action('after_setup_theme', 'iron_theme_setup');

if (!function_exists('iron_remove_page_editor')) {
    /**
     * Retire l'éditeur de contenu des pages.
     *
     * Conséquence voulue : Gutenberg est désactivé sur les pages et WordPress
     * retombe sur l'écran d'édition classique, où les meta boxes de la Brique 2
     * s'afficheront nativement.
     */
    function iron_remove_page_editor()
    {
        remove_post_type_support('page', 'editor');
    }
}
add_action('init', 'iron_remove_page_editor');
