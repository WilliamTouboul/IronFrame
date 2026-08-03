<?php
/**
 * Brique 3 — API de lecture côté template.
 *
 * C'est la seule surface que le développeur manipule au quotidien. Elle doit
 * rester aussi courte à écrire que l'appel ACF qu'elle remplace, sans quoi la
 * brique a raté sa cible.
 *
 *     <h1><?= iron_field('hero.title') ?></h1>
 *     <?= iron_image('hero.image', '16_9', ['class' => 'hero__bg']) ?>
 *     <?= iron_link('hero.cta', ['class' => 'btn']) ?>
 *
 * Toutes ces fonctions RETOURNENT une chaîne déjà échappée. Sortir du HTML brut
 * demande un appel explicite à `iron_field_raw()` — c'est le geste délibéré
 * exigé par la Brique 4.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_field')) {
    /**
     * Valeur d'un champ, échappée selon son type.
     *
     * @param string           $path Chemin `groupe.champ`.
     * @param int|WP_Post|null $post Page ciblée. Par défaut, la page courante.
     * @return mixed Chaîne échappée pour les types texte, entier pour `image`,
     *               tableau échappé pour `link`.
     */
    function iron_field($path, $post = null)
    {
        $field = _iron_resolve_field($path, $post);

        if (!$field) {
            return '';
        }

        $type = iron_field_type($field['type']);

        if (!$type || !isset($type['escape']) || !is_callable($type['escape'])) {
            _iron_debug_warning(sprintf('le type « %s » ne déclare pas de callback `escape`.', $field['type']));

            return '';
        }

        return call_user_func($type['escape'], iron_get_raw_value($field, $post));
    }
}

if (!function_exists('iron_has')) {
    /**
     * Le champ est-il rempli ?
     *
     * À utiliser pour conditionner un bloc de markup : inutile de sortir une
     * balise vide autour d'un champ que le client n'a pas renseigné.
     *
     * @param string           $path
     * @param int|WP_Post|null $post
     * @return bool
     */
    function iron_has($path, $post = null)
    {
        $field = _iron_resolve_field($path, $post);

        if (!$field) {
            return false;
        }

        $value = iron_get_raw_value($field, $post);
        $type  = iron_field_type($field['type']);

        if ($type && isset($type['is_filled']) && is_callable($type['is_filled'])) {
            return (bool) call_user_func($type['is_filled'], $value);
        }

        return !empty($value);
    }
}

if (!function_exists('iron_image')) {
    /**
     * Balise <img> complète, avec `srcset` et `loading` gérés par le core.
     *
     * Le format est choisi ici, au rendu, et non dans le schéma : le même
     * template peut ainsi servir plusieurs gabarits.
     *
     * @param string           $path
     * @param string           $size Taille enregistrée (`16_9`, `4_3`, `full`…).
     * @param array            $attr Attributs HTML additionnels.
     * @param int|WP_Post|null $post
     * @return string Chaîne vide si le champ est vide.
     */
    function iron_image($path, $size = 'full', $attr = [], $post = null)
    {
        $field = _iron_resolve_field($path, $post, 'image');

        if (!$field) {
            return '';
        }

        $attachment_id = absint(iron_get_raw_value($field, $post));

        if (!$attachment_id) {
            return '';
        }

        // Balise produite par le core : les attributs y sont déjà échappés.
        return wp_get_attachment_image($attachment_id, $size, false, $attr);
    }
}

if (!function_exists('iron_image_url')) {
    /**
     * URL seule d'une image, pour les cas où la balise <img> ne convient pas
     * (fond CSS, meta Open Graph, attribut `poster`…).
     *
     * Sans cette fonction, le développeur passerait par `iron_field_raw()` et
     * perdrait l'échappement par défaut — c'est précisément ce qu'on veut
     * éviter.
     *
     * @param string           $path
     * @param string           $size
     * @param int|WP_Post|null $post
     * @return string URL échappée, ou chaîne vide.
     */
    function iron_image_url($path, $size = 'full', $post = null)
    {
        $field = _iron_resolve_field($path, $post, 'image');

        if (!$field) {
            return '';
        }

        $attachment_id = absint(iron_get_raw_value($field, $post));

        if (!$attachment_id) {
            return '';
        }

        $url = wp_get_attachment_image_url($attachment_id, $size);

        return $url ? esc_url($url) : '';
    }
}

if (!function_exists('iron_link')) {
    /**
     * Balise <a> complète.
     *
     * Si le client n'a pas saisi de texte, l'adresse sert de libellé : mieux
     * vaut un lien moche qu'un lien invisible et non cliquable.
     *
     * @param string           $path
     * @param array            $attr Attributs HTML additionnels (`class`…).
     * @param int|WP_Post|null $post
     * @return string Chaîne vide si aucune adresse n'est renseignée.
     */
    function iron_link($path, $attr = [], $post = null)
    {
        $field = _iron_resolve_field($path, $post, 'link');

        if (!$field) {
            return '';
        }

        $link = iron_get_raw_value($field, $post);

        if (!is_array($link) || '' === $link['url']) {
            return '';
        }

        $label = '' !== $link['label'] ? $link['label'] : $link['url'];

        $attr = is_array($attr) ? $attr : [];
        $attr['href'] = $link['url'];

        if ('_blank' === $link['target']) {
            $attr['target'] = '_blank';

            // `noopener` n'est pas cosmétique : sans lui, la page ouverte garde
            // une référence vers la nôtre via window.opener.
            $rel = isset($attr['rel']) ? $attr['rel'] . ' ' : '';
            $attr['rel'] = trim($rel . 'noopener noreferrer');
        }

        return sprintf(
            '<a %s>%s</a>',
            _iron_build_attributes($attr),
            esc_html($label)
        );
    }
}

if (!function_exists('iron_field_raw')) {
    /**
     * Valeur brute, NON échappée.
     *
     * Réservée aux cas où le développeur produit lui-même le markup et prend
     * la responsabilité de l'échappement. Ne jamais faire transiter le retour
     * de cette fonction directement dans du HTML.
     *
     * @param string           $path
     * @param int|WP_Post|null $post
     * @return mixed Chaîne vide si le champ n'existe pas.
     */
    function iron_field_raw($path, $post = null)
    {
        $field = _iron_resolve_field($path, $post);

        return $field ? iron_get_raw_value($field, $post) : '';
    }
}

/* -------------------------------------------------------------------------- */
/* Interne                                                                    */
/* -------------------------------------------------------------------------- */

if (!function_exists('_iron_resolve_field')) {
    /**
     * Retrouve un champ et vérifie éventuellement son type.
     *
     * Un chemin qui ne correspond à rien est presque toujours une faute de
     * frappe du développeur : on le signale bruyamment en développement plutôt
     * que de renvoyer une chaîne vide qu'il mettra une heure à diagnostiquer.
     *
     * @param string           $path
     * @param int|WP_Post|null $post
     * @param string           $expected_type Type attendu, ou chaîne vide.
     * @return array|null
     */
    function _iron_resolve_field($path, $post = null, $expected_type = '')
    {
        $field = iron_get_field_definition($path, $post);

        if (!$field) {
            _iron_debug_warning(sprintf('champ inconnu « %s » sur cette page.', $path));

            return null;
        }

        if ('' !== $expected_type && $expected_type !== $field['type']) {
            _iron_debug_warning(sprintf(
                'le champ « %s » est de type « %s », mais il est lu comme un « %s ».',
                $path,
                $field['type'],
                $expected_type
            ));

            return null;
        }

        return $field;
    }
}

if (!function_exists('_iron_build_attributes')) {
    /**
     * Assemble une liste d'attributs HTML échappés.
     *
     * @param array $attr
     * @return string
     */
    function _iron_build_attributes(array $attr)
    {
        $out = [];

        foreach ($attr as $name => $value) {
            if (null === $value || false === $value) {
                continue;
            }

            $name = preg_replace('/[^a-zA-Z0-9_:-]/', '', (string) $name);

            if ('' === $name) {
                continue;
            }

            $value = ('href' === $name || 'src' === $name)
                ? esc_url((string) $value)
                : esc_attr((string) $value);

            $out[] = sprintf('%s="%s"', $name, $value);
        }

        return implode(' ', $out);
    }
}
