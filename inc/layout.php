<?php
/**
 * Enveloppe de mise en page.
 *
 * Les fichiers de `pages/` sont désormais des templates de page natifs
 * WordPress : ils sont appelés directement par le core, et non plus inclus
 * depuis index.php. Ils doivent donc ouvrir et fermer eux-mêmes le document.
 *
 * Ces deux fonctions ramènent ce passage obligé à une ligne de chaque côté, ce
 * qui préserve l'intention d'origine : un fichier de page ne contient que du
 * contenu.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_header')) {
    /**
     * Ouvre le document : <head>, <body>, en-tête du site, <main>.
     */
    function iron_header()
    {
        get_header();
        get_template_part('templates/header_on_site');
        echo '<main>';
    }
}

if (!function_exists('iron_footer')) {
    /**
     * Ferme le document : </main>, pied de page du site, scripts.
     */
    function iron_footer()
    {
        echo '</main>';
        get_template_part('templates/footer_on_site');
        get_footer();
    }
}
