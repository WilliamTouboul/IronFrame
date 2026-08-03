<?php
/**
 * Brique 1 — Registre des types de champs.
 *
 * Un type décrit ce qu'une valeur EST : sa valeur par défaut, la façon dont
 * elle est nettoyée avant d'entrer en base, et le contrôle de formulaire qui
 * permet de la saisir. Il ne décrit pas son rendu en front (Brique 3).
 *
 * Ajouter un type ne demande aucune modification du noyau : il suffit de
 * greffer une entrée sur le filtre `iron_field_types`. Les callbacks `render`
 * vivent dans `inc/fields/admin/render.php`, chargé uniquement en admin.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_field_types')) {
    /**
     * Retourne les types de champs disponibles, indexés par identifiant.
     *
     * @return array<string, array>
     */
    function iron_field_types()
    {
        static $types = null;

        if (null !== $types) {
            return $types;
        }

        $types = [

            'text' => [
                'label'    => __('Texte court', 'ironframe'),
                'default'  => '',
                'sanitize' => 'iron_sanitize_text',
                'render'   => 'iron_render_text_field',
                'escape'   => 'iron_escape_text',
            ],

            'textarea' => [
                'label'    => __('Texte long', 'ironframe'),
                'default'  => '',
                'sanitize' => 'iron_sanitize_textarea',
                'render'   => 'iron_render_textarea_field',
                'escape'   => 'iron_escape_textarea',
            ],

            'image' => [
                // La valeur stockée est un ID d'attachement, pas une URL : le
                // format d'affichage se choisit au rendu, et l'image reste
                // valide si le site change de domaine.
                'label'     => __('Image', 'ironframe'),
                'default'   => 0,
                'sanitize'  => 'iron_sanitize_image',
                'render'    => 'iron_render_image_field',
                'escape'    => 'iron_escape_image',
                // Type composé : le libellé du groupe ne cible aucun contrôle.
                'label_for' => false,
            ],

            'link' => [
                'label'     => __('Lien', 'ironframe'),
                'default'   => [
                    'url'    => '',
                    'label'  => '',
                    'target' => '_self',
                ],
                'sanitize'  => 'iron_sanitize_link',
                'render'    => 'iron_render_link_field',
                'escape'    => 'iron_escape_link',
                // Un lien vide reste un tableau non vide : `empty()` ne suffit
                // pas à décider s'il est rempli.
                'is_filled' => 'iron_link_is_filled',
                'label_for' => false,
            ],

        ];

        /**
         * Permet d'ajouter ou de modifier des types de champs.
         *
         * @param array<string, array> $types
         */
        $types = apply_filters('iron_field_types', $types);

        return $types;
    }
}

if (!function_exists('iron_field_type_exists')) {
    /**
     * @param string $type
     * @return bool
     */
    function iron_field_type_exists($type)
    {
        return isset(iron_field_types()[$type]);
    }
}

if (!function_exists('iron_field_type')) {
    /**
     * Retourne la définition d'un type, ou null s'il n'existe pas.
     *
     * @param string $type
     * @return array|null
     */
    function iron_field_type($type)
    {
        $types = iron_field_types();

        return isset($types[$type]) ? $types[$type] : null;
    }
}

/* -------------------------------------------------------------------------- */
/* Nettoyage des valeurs en entrée                                            */
/* -------------------------------------------------------------------------- */

if (!function_exists('iron_sanitize_text')) {
    /**
     * @param mixed $value
     * @return string
     */
    function iron_sanitize_text($value)
    {
        return is_scalar($value) ? sanitize_text_field((string) $value) : '';
    }
}

if (!function_exists('iron_sanitize_textarea')) {
    /**
     * Conserve les retours à la ligne, retire tout le HTML.
     *
     * @param mixed $value
     * @return string
     */
    function iron_sanitize_textarea($value)
    {
        return is_scalar($value) ? sanitize_textarea_field((string) $value) : '';
    }
}

if (!function_exists('iron_sanitize_image')) {
    /**
     * Vérifie que la valeur est bien un ID d'attachement existant.
     *
     * Un ID qui pointe vers un post supprimé ou vers autre chose qu'un média
     * est ramené à 0 : mieux vaut un champ vide qu'une référence fantôme.
     *
     * @param mixed $value
     * @return int
     */
    function iron_sanitize_image($value)
    {
        $id = absint($value);

        if (!$id || 'attachment' !== get_post_type($id)) {
            return 0;
        }

        return $id;
    }
}

if (!function_exists('iron_sanitize_link')) {
    /**
     * Nettoie les trois composantes d'un lien.
     *
     * `esc_url_raw` filtre les protocoles autorisés, ce qui neutralise au
     * passage les `javascript:` saisis dans le champ URL.
     *
     * @param mixed $value
     * @return array{url: string, label: string, target: string}
     */
    function iron_sanitize_link($value)
    {
        $value = is_array($value) ? $value : [];

        $target = isset($value['target']) ? $value['target'] : '_self';

        return [
            'url'    => isset($value['url']) ? esc_url_raw(trim((string) $value['url'])) : '',
            'label'  => isset($value['label']) ? sanitize_text_field((string) $value['label']) : '',
            'target' => in_array($target, ['_self', '_blank'], true) ? $target : '_self',
        ];
    }
}

if (!function_exists('iron_sanitize_field_value')) {
    /**
     * Applique le nettoyage correspondant au type d'un champ.
     *
     * @param mixed $value
     * @param array $field Champ normalisé (issu du schéma).
     * @return mixed
     */
    function iron_sanitize_field_value($value, array $field)
    {
        $type = iron_field_type($field['type']);

        if (!$type || !is_callable($type['sanitize'])) {
            return '';
        }

        return call_user_func($type['sanitize'], $value);
    }
}

/* -------------------------------------------------------------------------- */
/* Échappement en sortie                                                      */
/* -------------------------------------------------------------------------- */

if (!function_exists('iron_escape_text')) {
    /**
     * @param mixed $value
     * @return string
     */
    function iron_escape_text($value)
    {
        return esc_html((string) $value);
    }
}

if (!function_exists('iron_escape_textarea')) {
    /**
     * Échappe puis restitue les retours à la ligne saisis par le client.
     *
     * `nl2br` plutôt que `wpautop` : le texte reste à l'intérieur de la balise
     * écrite par le développeur dans le template, au lieu d'injecter des
     * paragraphes dans son markup.
     *
     * @param mixed $value
     * @return string
     */
    function iron_escape_textarea($value)
    {
        return nl2br(esc_html((string) $value));
    }
}

if (!function_exists('iron_escape_image')) {
    /**
     * Un ID d'attachement n'a rien à échapper, il a juste à rester un entier.
     *
     * @param mixed $value
     * @return int
     */
    function iron_escape_image($value)
    {
        return absint($value);
    }
}

if (!function_exists('iron_escape_link')) {
    /**
     * @param mixed $value
     * @return array{url: string, label: string, target: string}
     */
    function iron_escape_link($value)
    {
        $value = is_array($value) ? $value : [];

        return [
            'url'    => isset($value['url']) ? esc_url((string) $value['url']) : '',
            'label'  => isset($value['label']) ? esc_html((string) $value['label']) : '',
            'target' => isset($value['target']) ? esc_attr((string) $value['target']) : '_self',
        ];
    }
}

if (!function_exists('iron_link_is_filled')) {
    /**
     * Un lien est rempli dès lors qu'il a une adresse.
     *
     * @param mixed $value
     * @return bool
     */
    function iron_link_is_filled($value)
    {
        return is_array($value) && !empty($value['url']);
    }
}

/* -------------------------------------------------------------------------- */
/* Diagnostic                                                                 */
/* -------------------------------------------------------------------------- */

if (!function_exists('_iron_debug_warning')) {
    /**
     * Signale une erreur de développeur, uniquement quand WP_DEBUG est actif.
     *
     * Jamais fatal : une erreur de déclaration ou d'appel doit priver le site
     * d'un champ, pas le mettre à terre chez le client.
     *
     * @param string $message
     * @return void
     */
    function _iron_debug_warning($message)
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        trigger_error('Ironframe — ' . $message, E_USER_WARNING);
    }
}
