<?php
/**
 * Brique 5 — Restrictions appliquées côté serveur.
 *
 * Ce fichier est chargé en front comme en admin : les garde-fous doivent tenir
 * quel que soit le point d'entrée, y compris l'API REST.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_lock_page_creation')) {
    /**
     * Bascule la capacité de création du type `page` vers une capacité dédiée.
     *
     * Par défaut, WordPress utilise `edit_pages` à la fois pour créer et pour
     * modifier : impossible de retirer l'un sans l'autre. On remplace donc la
     * capacité de création par `iron_create_pages`, accordée aux
     * administrateurs et aux éditeurs, jamais au client.
     *
     * L'objet de type est modifié à l'`init` plutôt que par le filtre
     * `register_post_type_args` : les types natifs sont enregistrés par
     * `wp-settings.php` avant le chargement du thème, le filtre arriverait
     * trop tard.
     *
     * Effets obtenus gratuitement, parce que le core interroge cette capacité :
     * `post-new.php` refuse l'accès, le bouton « Ajouter » disparaît de la
     * liste des pages, et l'entrée de menu correspondante n'est pas rendue.
     *
     * @return void
     */
    function iron_lock_page_creation()
    {
        $page_type = get_post_type_object('page');

        if ($page_type) {
            $page_type->cap->create_posts = IRON_CAP_CREATE_PAGES;
        }
    }
}
add_action('init', 'iron_lock_page_creation', 20);

if (!function_exists('iron_lock_page_structure')) {
    /**
     * Fige ce qui ne relève pas du contenu quand c'est un client qui enregistre.
     *
     * Le statut, le permalien et le parent ne sont pas des champs éditoriaux :
     * ce sont des éléments de structure dont dépendent les liens du site et son
     * référencement. Un client qui dépublie sa page d'accueil par mégarde n'a
     * aucun moyen de comprendre ce qui s'est passé.
     *
     * @param array $data    Données préparées pour la base.
     * @param array $postarr Données soumises.
     * @return array
     */
    function iron_lock_page_structure($data, $postarr)
    {
        if ('page' !== $data['post_type'] || !iron_user_is_client()) {
            return $data;
        }

        $post_id = isset($postarr['ID']) ? absint($postarr['ID']) : 0;

        if (!$post_id) {
            return $data;
        }

        $existing = get_post($post_id);

        if (!$existing) {
            return $data;
        }

        $data['post_status'] = $existing->post_status;
        $data['post_name']   = $existing->post_name;
        $data['post_parent'] = $existing->post_parent;

        return $data;
    }
}
add_filter('wp_insert_post_data', 'iron_lock_page_structure', 10, 2);

if (!function_exists('iron_lock_page_template')) {
    /**
     * Empêche un client de changer le template d'une page.
     *
     * Le template détermine quels champs existent : en changer revient à faire
     * disparaître le contenu de l'écran, sans rien supprimer en base. C'est le
     * genre d'incident qui ressemble à une perte de données.
     *
     * La méta box est retirée de l'écran par ailleurs ; ce filtre est ce qui
     * rend le verrou réel.
     *
     * @param mixed  $check
     * @param int    $object_id
     * @param string $meta_key
     * @return mixed Null pour laisser faire, false pour bloquer l'écriture.
     */
    function iron_lock_page_template($check, $object_id, $meta_key)
    {
        if ('_wp_page_template' === $meta_key && iron_user_is_client()) {
            return false;
        }

        return $check;
    }
}
add_filter('update_post_metadata', 'iron_lock_page_template', 10, 3);
