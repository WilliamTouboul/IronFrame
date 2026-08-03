<?php
/**
 * Ironframe — point d'entrée du thème.
 *
 * Ce fichier ne contient QUE le chargement des modules de `inc/`.
 * Toute nouvelle fonctionnalité va dans un module dédié, jamais ici.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

define('IRON_VERSION', '1.0.0');
define('IRON_PATH', get_stylesheet_directory());
define('IRON_URI', get_stylesheet_directory_uri());

$iron_modules = [
    'inc/theme-setup.php',   // Supports du thème, tailles d'images, textdomain.
    'inc/cleanup.php',       // Nettoyage du <head> et désactivation des flux.
    'inc/assets.php',        // Chargement des CSS et JS.
    'inc/layout.php',        // Enveloppe de mise en page des templates.
    'inc/fields/types.php',  // Brique 1 — registre des types de champs.
    'inc/fields/schema.php', // Brique 1 — découverte et validation des schémas.
    'inc/fields/store.php',  // Lecture / écriture des valeurs.
    'inc/fields/api.php',    // Brique 3 — API de lecture côté template.

    // Brique 6 — options globales : les données qui n'appartiennent à aucune
    // page (coordonnées, réseaux sociaux, pied de page).
    'inc/options/schema.php',
    'inc/options/store.php',
    'inc/options/api.php',

    // Brique 5 — le rôle client. Chargé partout : les capacités et les verrous
    // à l'enregistrement doivent tenir aussi via l'API REST.
    'inc/client/role.php',
    'inc/client/restrictions.php',
];

// Modules réservés à l'écran d'administration.
if (is_admin()) {
    $iron_modules[] = 'inc/fields/admin/render.php';    // Brique 2 — contrôles de formulaire.
    $iron_modules[] = 'inc/fields/admin/meta-box.php';  // Brique 2 — meta boxes et sauvegarde.
    $iron_modules[] = 'inc/fields/admin/assets.php';    // Brique 2 — CSS et JS d'admin.
    $iron_modules[] = 'inc/options/admin.php';          // Brique 6 — écran des options globales.
    $iron_modules[] = 'inc/client/admin-ui.php';        // Brique 5 — épuration de l'admin.
}

foreach ($iron_modules as $iron_module) {
    require_once IRON_PATH . '/' . $iron_module;
}

unset($iron_modules, $iron_module);
