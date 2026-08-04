<?php
/**
 * Brique 6 — Lecture et écriture des options globales.
 *
 * Pendant exact de `inc/fields/store.php`, mais adossé à `wp_options` au lieu
 * de `wp_postmeta`. Mêmes règles : aucun échappement ici, et la valeur est
 * re-nettoyée à la lecture autant qu'à l'écriture.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_get_raw_option')) {
    /**
     * Valeur brute d'une option globale.
     *
     * La valeur par défaut ne s'applique que tant que l'option n'a jamais été
     * enregistrée, exactement comme pour les champs de page. On distingue
     * « jamais enregistrée » de « enregistrée vide » avec une sentinelle :
     * `get_option()` renvoie `false` dans les deux cas, ce qui ferait
     * réapparaître le défaut sur un champ que le client a délibérément vidé.
     *
     * @param array $field Champ normalisé issu du schéma d'options.
     * @return mixed
     */
    function iron_get_raw_option(array $field)
    {
        static $sentinel = null;

        if (null === $sentinel) {
            $sentinel = new stdClass();
        }

        $value = get_option($field['option_name'], $sentinel);

        if ($value === $sentinel) {
            return $field['default'];
        }

        return iron_sanitize_field_value($value, $field);
    }
}

if (!function_exists('iron_option_is_enabled')) {
    /**
     * Une section des réglages du site est-elle affichée ?
     *
     * @param string $group
     * @return bool
     */
    function iron_option_is_enabled($group)
    {
        $schema = iron_get_options_schema();

        if (!isset($schema[$group]) || empty($schema[$group]['toggle'])) {
            return true;
        }

        static $sentinel = null;

        if (null === $sentinel) {
            $sentinel = new stdClass();
        }

        $value = get_option(iron_option_group_toggle_name($group), $sentinel);

        if ($value === $sentinel) {
            return $schema[$group]['toggle_default'];
        }

        return '1' === (string) $value;
    }
}

if (!function_exists('iron_save_option_group_toggle')) {
    /**
     * @param string $group
     * @param bool   $enabled
     * @return void
     */
    function iron_save_option_group_toggle($group, $enabled)
    {
        update_option(iron_option_group_toggle_name($group), $enabled ? '1' : '0', true);
    }
}

if (!function_exists('iron_save_raw_option')) {
    /**
     * Nettoie puis enregistre une option globale.
     *
     * Chargée en autoload : ces valeurs (téléphone, réseaux sociaux, pied de
     * page) sont lues sur pratiquement toutes les pages du site.
     *
     * @param array $field
     * @param mixed $value Valeur soumise, déjà déslashée.
     * @return mixed Valeur réellement enregistrée.
     */
    function iron_save_raw_option(array $field, $value)
    {
        $clean = iron_sanitize_field_value($value, $field);

        update_option($field['option_name'], $clean, true);

        return $clean;
    }
}
