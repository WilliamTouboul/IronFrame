<?php
/**
 * Thème enfant d'Ironframe — point d'entrée du projet.
 *
 * Le moteur est chargé par le thème parent : il n'y a rien à inclure ici.
 * Ce fichier ne sert qu'à ce qui est propre au projet.
 *
 * Les styles sont enregistrés automatiquement par le parent, dans cet ordre :
 * son reset, puis vos `assets/style/*.css` s'ils existent, puis le `style.css`
 * de ce dossier. Vous n'avez donc rien à mettre en file d'attente.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

/*
 * Exemple — ajouter un type de champ propre au projet.
 * Le registre du moteur est extensible sans le modifier.
 *
 * add_filter('iron_field_types', function ($types) {
 *     $types['couleur'] = [
 *         'label'    => 'Couleur',
 *         'default'  => '',
 *         'sanitize' => 'mon_projet_sanitize_couleur',
 *         'render'   => 'mon_projet_render_couleur',
 *         'escape'   => 'esc_attr',
 *     ];
 *
 *     return $types;
 * });
 */

/*
 * Exemple — ouvrir une entrée de menu au rôle Client, par exemple celle d'une
 * extension de formulaires dont il doit changer l'adresse de réception.
 *
 * add_filter('iron_client_allowed_menus', function ($menus) {
 *     $menus[] = 'wpcf7';
 *
 *     return $menus;
 * });
 */
