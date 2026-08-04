<?php
/**
 * Brique 6 — API de lecture des options globales.
 *
 * Décalque de `inc/fields/api.php`, avec les mêmes garanties : tout retourne
 * une valeur déjà échappée, et sortir du brut demande un appel explicite.
 *
 *     <a href="tel:<?= iron_option('contact.phone') ?>">…</a>
 *     <?= iron_link_option('social.instagram', ['class' => 'social']) ?>
 *
 * Le préfixe `iron_option_*` est délibéré : lire une donnée globale n'est pas
 * la même chose que lire un champ de la page en cours, et confondre les deux
 * produirait des bugs silencieux.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_option')) {
    /**
     * Valeur d'une option globale, échappée selon son type.
     *
     * @param string $path Chemin `groupe.champ`.
     * @return mixed
     */
    function iron_option($path)
    {
        $field = _iron_resolve_option($path);

        if (!$field) {
            return '';
        }

        if ('repeater' === $field['type']) {
            _iron_debug_warning(sprintf('« %s » est une liste répétable : utilisez iron_option_rows().', $path));

            return '';
        }

        $type = iron_field_type($field['type']);

        if (!$type || !isset($type['escape']) || !is_callable($type['escape'])) {
            _iron_debug_warning(sprintf('le type « %s » ne déclare pas de callback `escape`.', $field['type']));

            return '';
        }

        return call_user_func($type['escape'], iron_get_raw_option($field), $field);
    }
}

if (!function_exists('iron_option_rows')) {
    /**
     * Lignes d'une liste répétable globale.
     *
     * Les lignes retournées se manipulent avec les mêmes accesseurs que celles
     * des pages : `iron_row()`, `iron_row_has()`, `iron_row_image()`,
     * `iron_row_link()`.
     *
     * @param string $path
     * @return array<int, array>
     */
    function iron_option_rows($path)
    {
        $field = _iron_resolve_option($path, 'repeater');

        if (!$field) {
            return [];
        }

        return _iron_build_rows(iron_get_raw_option($field), $field);
    }
}

if (!function_exists('iron_option_has')) {
    /**
     * L'option est-elle remplie ?
     *
     * @param string $path
     * @return bool
     */
    function iron_option_has($path)
    {
        $field = _iron_resolve_option($path);

        if (!$field) {
            return false;
        }

        $value = iron_get_raw_option($field);
        $type  = iron_field_type($field['type']);

        if ($type && isset($type['is_filled']) && is_callable($type['is_filled'])) {
            return (bool) call_user_func($type['is_filled'], $value);
        }

        return !empty($value);
    }
}

if (!function_exists('iron_option_image')) {
    /**
     * Balise <img> d'une image globale (logo, visuel de partage…).
     *
     * @param string $path
     * @param string $size
     * @param array  $attr
     * @return string
     */
    function iron_option_image($path, $size = 'full', $attr = [])
    {
        $field = _iron_resolve_option($path, 'image');

        if (!$field) {
            return '';
        }

        $attachment_id = absint(iron_get_raw_option($field));

        if (!$attachment_id) {
            return '';
        }

        return wp_get_attachment_image($attachment_id, $size, false, $attr);
    }
}

if (!function_exists('iron_option_image_url')) {
    /**
     * @param string $path
     * @param string $size
     * @return string
     */
    function iron_option_image_url($path, $size = 'full')
    {
        $field = _iron_resolve_option($path, 'image');

        if (!$field) {
            return '';
        }

        $attachment_id = absint(iron_get_raw_option($field));

        if (!$attachment_id) {
            return '';
        }

        $url = wp_get_attachment_image_url($attachment_id, $size);

        return $url ? esc_url($url) : '';
    }
}

if (!function_exists('iron_option_link')) {
    /**
     * Balise <a> d'un lien global (réseau social, mentions légales…).
     *
     * @param string $path
     * @param array  $attr
     * @return string
     */
    function iron_option_link($path, $attr = [])
    {
        $field = _iron_resolve_option($path, 'link');

        if (!$field) {
            return '';
        }

        return _iron_build_link_tag(iron_get_raw_option($field), $attr);
    }
}

if (!function_exists('iron_option_raw')) {
    /**
     * Valeur brute, NON échappée. Geste explicite.
     *
     * @param string $path
     * @return mixed
     */
    function iron_option_raw($path)
    {
        $field = _iron_resolve_option($path);

        return $field ? iron_get_raw_option($field) : '';
    }
}

/* -------------------------------------------------------------------------- */
/* Interne                                                                    */
/* -------------------------------------------------------------------------- */

if (!function_exists('_iron_resolve_option')) {
    /**
     * @param string $path
     * @param string $expected_type
     * @return array|null
     */
    function _iron_resolve_option($path, $expected_type = '')
    {
        $field = iron_get_option_definition($path);

        if (!$field) {
            _iron_debug_warning(sprintf('option globale inconnue « %s ».', $path));

            return null;
        }

        // Section masquée : voir le commentaire équivalent dans fields/api.php.
        if (!iron_option_is_enabled($field['group'])) {
            return null;
        }

        if ('' !== $expected_type && $expected_type !== $field['type']) {
            _iron_debug_warning(sprintf(
                'l\'option « %s » est de type « %s », mais elle est lue comme un « %s ».',
                $path,
                $field['type'],
                $expected_type
            ));

            return null;
        }

        return $field;
    }
}
