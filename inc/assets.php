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
        $full_path = iron_locate($relative_path);

        return '' !== $full_path
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
        /*
         * Le reset vient toujours du moteur ; le reste vient du projet dès
         * qu'il le fournit. Un thème enfant qui pose son propre `main.css`
         * remplace donc celui du parent, sans avoir à désinscrire quoi que ce
         * soit.
         */
        wp_enqueue_style(
            'iron-reset',
            IRON_URI . '/style.css',
            [],
            iron_asset_version('style.css')
        );

        $iron_styles = [
            'iron-variables' => 'assets/style/variable.css',
            'iron-fonts'     => 'assets/style/fonts.css',
            'iron-main'      => 'assets/style/main.css',
        ];

        $iron_depend = 'iron-reset';

        foreach ($iron_styles as $iron_handle => $iron_file) {

            $iron_uri = iron_locate_uri($iron_file);

            if ('' === $iron_uri) {
                continue;
            }

            wp_enqueue_style($iron_handle, $iron_uri, [$iron_depend], iron_asset_version($iron_file));

            $iron_depend = $iron_handle;
        }

        // Si le thème enfant déclare une feuille de style à sa racine, elle
        // passe en dernier : c'est la convention que tout développeur attend.
        if (get_stylesheet_directory() !== get_template_directory()) {
            wp_enqueue_style(
                'iron-child',
                get_stylesheet_uri(),
                [$iron_depend],
                iron_asset_version('style.css')
            );
        }

        // Aucune librairie JS tierce n'est chargée par défaut. Le thème est
        // vendu comme un socle sans dépendance externe : le développeur qui
        // reprend le projet ajoute ici ce dont il a besoin, en local.
        $iron_script = iron_locate_uri('assets/js/main.js');

        if ('' !== $iron_script) {
            wp_enqueue_script(
                'iron-main',
                $iron_script,
                [],
                iron_asset_version('assets/js/main.js'),
                true
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'iron_enqueue_assets');
