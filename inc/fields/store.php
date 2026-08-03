<?php
/**
 * Lecture et écriture bas niveau des valeurs de champs.
 *
 * Couche partagée entre l'admin (Brique 2) et l'API de template (Brique 3).
 * Elle ne fait aucun échappement : elle rend la valeur telle qu'elle doit être
 * manipulée. L'échappement est la responsabilité du consommateur.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_get_raw_value')) {
    /**
     * Retourne la valeur brute d'un champ.
     *
     * La valeur par défaut déclarée dans le schéma s'applique uniquement tant
     * que le champ n'a jamais été enregistré. Une fois que le client a
     * enregistré la page, ce qu'il voit en admin est exactement ce que le front
     * affiche — y compris s'il a vidé le champ. Un champ qui se remplit tout
     * seul après avoir été vidé est un ticket de support garanti.
     *
     * @param array            $field Champ normalisé.
     * @param int|WP_Post|null $post
     * @return mixed
     */
    function iron_get_raw_value(array $field, $post = null)
    {
        $post = get_post($post);

        if (!$post || !metadata_exists('post', $post->ID, $field['meta_key'])) {
            return $field['default'];
        }

        $value = get_post_meta($post->ID, $field['meta_key'], true);

        // Re-nettoyage en lecture : la valeur a pu être écrite hors de notre
        // formulaire (import, WP-CLI, migration, écriture directe en base).
        // On ne fait jamais confiance à ce qui sort de la table postmeta.
        return iron_sanitize_field_value($value, $field);
    }
}

if (!function_exists('iron_save_raw_value')) {
    /**
     * Nettoie puis enregistre la valeur d'un champ.
     *
     * @param array $field   Champ normalisé.
     * @param mixed $value   Valeur soumise, déjà déslashée.
     * @param int   $post_id
     * @return mixed Valeur réellement enregistrée.
     */
    function iron_save_raw_value(array $field, $value, $post_id)
    {
        $clean = iron_sanitize_field_value($value, $field);

        update_post_meta($post_id, $field['meta_key'], $clean);

        return $clean;
    }
}
