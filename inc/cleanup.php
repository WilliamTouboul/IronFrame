<?php
/**
 * Nettoyage du <head> et désactivation des flux.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_clean_head')) {
    /**
     * Retire du <head> tout ce qui n'a pas d'usage sur un site vitrine.
     */
    function iron_clean_head()
    {
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }
}
add_action('init', 'iron_clean_head');

if (!function_exists('iron_disable_feed')) {
    /**
     * Coupe les flux RSS/Atom.
     */
    function iron_disable_feed()
    {
        wp_die(
            esc_html__('Aucun flux disponible, merci de consulter la page d\'accueil.', 'ironframe'),
            '',
            ['response' => 404]
        );
    }
}

foreach ([
    'do_feed',
    'do_feed_rdf',
    'do_feed_rss',
    'do_feed_rss2',
    'do_feed_atom',
    'do_feed_rss2_comments',
    'do_feed_atom_comments',
] as $iron_feed_hook) {
    add_action($iron_feed_hook, 'iron_disable_feed', 1);
}
unset($iron_feed_hook);
