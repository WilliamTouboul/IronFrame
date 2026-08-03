<?php
/**
 * Chargement des feuilles de style et des scripts.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_asset_version')) {
    /**
     * Version d'un asset basée sur sa date de modification.
     *
     * Évite d'avoir à bumper un numéro de version à la main : le navigateur
     * recharge le fichier dès qu'il change réellement.
     *
     * @param string $relative_path Chemin relatif au dossier du thème.
     * @return string
     */
    function iron_asset_version($relative_path)
    {
        $full_path = IRON_PATH . '/' . ltrim($relative_path, '/');

        return file_exists($full_path)
            ? (string) filemtime($full_path)
            : IRON_VERSION;
    }
}

if (!function_exists('iron_enqueue_assets')) {
    /**
     * Enregistre les assets du thème.
     *
     * L'ordre compte : le reset d'abord, les variables ensuite, le CSS métier
     * en dernier.
     */
    function iron_enqueue_assets()
    {
        // Reset — c'est le style.css à la racine du thème.
        wp_enqueue_style(
            'iron-reset',
            get_stylesheet_uri(),
            [],
            iron_asset_version('style.css')
        );

        wp_enqueue_style(
            'iron-variables',
            IRON_URI . '/assets/style/variable.css',
            ['iron-reset'],
            iron_asset_version('assets/style/variable.css')
        );

        wp_enqueue_style(
            'iron-fonts',
            IRON_URI . '/assets/style/fonts.css',
            ['iron-variables'],
            iron_asset_version('assets/style/fonts.css')
        );

        wp_enqueue_style(
            'iron-main',
            IRON_URI . '/assets/style/main.css',
            ['iron-fonts'],
            iron_asset_version('assets/style/main.css')
        );

        // Aucune librairie JS tierce n'est chargée par défaut. Le thème est
        // vendu comme un socle sans dépendance externe : le développeur qui
        // reprend le projet ajoute ici ce dont il a besoin, en local.
        wp_enqueue_script(
            'iron-main',
            IRON_URI . '/assets/js/main.js',
            [],
            iron_asset_version('assets/js/main.js'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'iron_enqueue_assets');
