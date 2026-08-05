<?php
/**
 * Brique 1 — Découverte, normalisation et validation des schémas de champs.
 *
 * Un schéma est un fichier PHP posé à côté du template qu'il alimente et qui
 * retourne un tableau :
 *
 *     pages/home.php         Template Name: Accueil
 *     pages/home.fields.php  return [ 'hero' => [ 'label' => ..., 'fields' => [...] ] ];
 *
 * Aucun enregistrement n'est nécessaire : le rattachement se fait par le nom
 * du fichier. Ajouter une page = ajouter deux fichiers.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

/**
 * Préfixe de toutes les clés de meta écrites par le thème.
 *
 * L'underscore initial est significatif : il masque la valeur de la meta box
 * « Champs personnalisés » native. Le client ne doit jamais voir les données
 * brutes.
 */
define('IRON_META_PREFIX', '_iron_');

if (!function_exists('iron_meta_key')) {
    /**
     * Construit la clé de meta d'un champ.
     *
     * @param string $group Identifiant du groupe.
     * @param string $field Identifiant du champ.
     * @return string Ex. `_iron_hero_title`.
     */
    function iron_meta_key($group, $field)
    {
        return IRON_META_PREFIX . $group . '_' . $field;
    }
}

/**
 * Préfixe des options globales (Brique 6).
 *
 * Pas d'underscore initial ici : contrairement aux metas, une option n'est
 * jamais exposée dans une interface qu'il faudrait masquer au client.
 */
define('IRON_OPTION_PREFIX', 'iron_opt_');

if (!function_exists('iron_group_toggle_key')) {
    /**
     * Clé de meta portant l'interrupteur d'affichage d'un groupe.
     *
     * Le suffixe utilise un tiret, caractère **interdit dans un identifiant**
     * de groupe ou de champ. C'est ce qui rend la collision impossible : sans
     * lui, un groupe nommé `hero_title` et un champ `hero.title` produiraient
     * exactement la même clé.
     *
     * @param string $group
     * @return string Ex. `_iron_promo-on`.
     */
    function iron_group_toggle_key($group)
    {
        return IRON_META_PREFIX . $group . '-on';
    }
}

if (!function_exists('iron_option_name')) {
    /**
     * Construit le nom d'option d'un champ global.
     *
     * @param string $group
     * @param string $field
     * @return string Ex. `iron_opt_contact_phone`.
     */
    function iron_option_name($group, $field)
    {
        return IRON_OPTION_PREFIX . $group . '_' . $field;
    }
}

if (!function_exists('iron_option_group_toggle_name')) {
    /**
     * Nom d'option portant l'interrupteur d'affichage d'un groupe global.
     *
     * @param string $group
     * @return string Ex. `iron_opt_promo-on`.
     */
    function iron_option_group_toggle_name($group)
    {
        return IRON_OPTION_PREFIX . $group . '-on';
    }
}

if (!function_exists('iron_schema_file_for_template')) {
    /**
     * Déduit le chemin absolu du schéma associé à un template.
     *
     * @param string $template Chemin relatif au thème, ex. `pages/home.php`.
     * @return string Chemin absolu, ou chaîne vide si aucun schéma valide.
     */
    function iron_schema_file_for_template($template)
    {
        $template = (string) $template;

        if ('' === $template || 'default' === $template || !preg_match('/\.php$/', $template)) {
            return '';
        }

        // L'enfant l'emporte sur le parent : un projet peut redéfinir le schéma
        // d'un gabarit livré sans toucher au moteur.
        $path = iron_locate(preg_replace('/\.php$/', '.fields.php', $template));

        if ('' === $path || !iron_path_is_inside_theme($path)) {
            return '';
        }

        return realpath($path);
    }
}

if (!function_exists('iron_get_schema')) {
    /**
     * Charge, normalise et met en cache le schéma d'un template.
     *
     * @param string $template Chemin relatif au thème, ex. `pages/home.php`.
     * @return array<string, array> Groupes normalisés, indexés par identifiant.
     */
    function iron_get_schema($template)
    {
        static $cache = [];

        $template = (string) $template;

        if (isset($cache[$template])) {
            return $cache[$template];
        }

        $file = iron_schema_file_for_template($template);

        if ('' === $file) {
            $cache[$template] = [];

            return $cache[$template];
        }

        $raw = require $file;

        if (!is_array($raw)) {
            _iron_schema_warning(
                sprintf('le fichier doit retourner un tableau (%s reçu).', gettype($raw)),
                $template
            );

            $cache[$template] = [];

            return $cache[$template];
        }

        $cache[$template] = _iron_normalize_schema($raw, $template);

        return $cache[$template];
    }
}

if (!function_exists('iron_get_post_schema')) {
    /**
     * Retourne le schéma de la page demandée, d'après son template.
     *
     * @param int|WP_Post|null $post
     * @return array<string, array>
     */
    function iron_get_post_schema($post = null)
    {
        $post = get_post($post);

        if (!$post) {
            return [];
        }

        return iron_get_schema(get_page_template_slug($post));
    }
}

if (!function_exists('iron_flatten_schema')) {
    /**
     * Aplatit un schéma en une liste de champs indexée par chemin.
     *
     * Pratique pour la sauvegarde (Brique 2) et la lecture (Brique 3), qui
     * travaillent par chemin `groupe.champ` sans se soucier des groupes.
     *
     * @param array $schema
     * @return array<string, array>
     */
    function iron_flatten_schema(array $schema)
    {
        $flat = [];

        foreach ($schema as $group) {
            foreach ($group['fields'] as $field) {
                $flat[$field['path']] = $field;
            }
        }

        return $flat;
    }
}

if (!function_exists('iron_get_field_definition')) {
    /**
     * Retrouve la définition normalisée d'un champ à partir de son chemin.
     *
     * @param string           $path Chemin `groupe.champ`.
     * @param int|WP_Post|null $post
     * @return array|null
     */
    function iron_get_field_definition($path, $post = null)
    {
        $flat = iron_flatten_schema(iron_get_post_schema($post));

        return isset($flat[$path]) ? $flat[$path] : null;
    }
}

/* -------------------------------------------------------------------------- */
/* Normalisation et validation                                                */
/* -------------------------------------------------------------------------- */

if (!function_exists('_iron_normalize_schema')) {
    /**
     * Valide un schéma brut et le complète avec ses valeurs calculées.
     *
     * Une entrée invalide est ignorée, pas fatale : une déclaration ratée doit
     * priver le client d'un champ, jamais casser le site. L'erreur est en
     * revanche signalée bruyamment quand WP_DEBUG est actif.
     *
     * @param array  $raw
     * @param string $template Sert d'étiquette dans les messages d'erreur.
     * @param string $context  `post` pour un schéma de page, `option` pour un
     *                         schéma global : détermine où la valeur est
     *                         stockée, et donc quelle clé est calculée.
     * @return array<string, array>
     */
    function _iron_normalize_schema(array $raw, $template, $context = 'post')
    {
        $schema = [];

        foreach ($raw as $group_key => $group) {

            if (!_iron_is_valid_key($group_key)) {
                _iron_schema_warning(
                    sprintf('identifiant de groupe invalide « %s » (attendu : minuscules, chiffres et underscores).', $group_key),
                    $template
                );
                continue;
            }

            if (!is_array($group) || empty($group['fields']) || !is_array($group['fields'])) {
                _iron_schema_warning(
                    sprintf('le groupe « %s » doit contenir une clé `fields` non vide.', $group_key),
                    $template
                );
                continue;
            }

            $fields = [];

            foreach ($group['fields'] as $field_key => $field) {

                if (!_iron_is_valid_key($field_key)) {
                    _iron_schema_warning(
                        sprintf('identifiant de champ invalide « %s » dans le groupe « %s ».', $field_key, $group_key),
                        $template
                    );
                    continue;
                }

                if (!is_array($field) || empty($field['type'])) {
                    _iron_schema_warning(
                        sprintf('le champ « %s.%s » doit déclarer une clé `type`.', $group_key, $field_key),
                        $template
                    );
                    continue;
                }

                if (!iron_field_type_exists($field['type'])) {
                    _iron_schema_warning(
                        sprintf(
                            'type inconnu « %s » sur le champ « %s.%s » (types disponibles : %s).',
                            $field['type'],
                            $group_key,
                            $field_key,
                            implode(', ', array_keys(iron_field_types()))
                        ),
                        $template
                    );
                    continue;
                }

                $type = iron_field_type($field['type']);

                // Un champ ne porte que la clé de stockage de son contexte :
                // une page n'a pas de nom d'option, une option n'a pas de clé
                // de meta.
                $storage = ('option' === $context)
                    ? ['option_name' => iron_option_name($group_key, $field_key)]
                    : ['meta_key' => iron_meta_key($group_key, $field_key)];

                $extra = [];

                if ('select' === $field['type']) {
                    $options = isset($field['options']) && is_array($field['options'])
                        ? $field['options']
                        : [];

                    if (!$options) {
                        _iron_schema_warning(
                            sprintf('la liste de choix « %s.%s » doit déclarer une clé `options` non vide.', $group_key, $field_key),
                            $template
                        );
                        continue;
                    }

                    // Les clés sont normalisées en chaînes : PHP transforme
                    // silencieusement une clé numérique en entier, et la
                    // comparaison stricte à la sauvegarde échouerait.
                    $clean_options = [];

                    foreach ($options as $option_key => $option_label) {
                        $clean_options[(string) $option_key] = (string) $option_label;
                    }

                    $extra = ['options' => $clean_options];
                }

                if ('repeater' === $field['type']) {
                    $sub_fields = _iron_normalize_subfields(
                        isset($field['fields']) ? $field['fields'] : [],
                        $template,
                        $group_key . '.' . $field_key
                    );

                    if (!$sub_fields) {
                        _iron_schema_warning(
                            sprintf('le répétable « %s.%s » doit déclarer au moins un sous-champ valide dans `fields`.', $group_key, $field_key),
                            $template
                        );
                        continue;
                    }

                    $extra = [
                        'fields'    => $sub_fields,
                        'min'       => isset($field['min']) ? max(0, (int) $field['min']) : 0,
                        'max'       => isset($field['max']) ? max(0, (int) $field['max']) : 0,
                        'label_add' => isset($field['label_add'])
                            ? (string) $field['label_add']
                            : __('Ajouter une ligne', 'ironframe'),
                        'label_row' => isset($field['label_row'])
                            ? (string) $field['label_row']
                            : __('Ligne', 'ironframe'),
                    ];
                }

                $fields[$field_key] = array_merge([
                    'key'      => $field_key,
                    'group'    => $group_key,
                    'path'     => $group_key . '.' . $field_key,
                    'type'     => $field['type'],
                    'label'    => isset($field['label']) ? (string) $field['label'] : _iron_humanize($field_key),
                    'desc'     => isset($field['desc']) ? (string) $field['desc'] : '',
                    'default'  => array_key_exists('default', $field) ? $field['default'] : $type['default'],
                    'required' => !empty($field['required']),
                ], $storage, $extra);
            }

            if (empty($fields)) {
                continue;
            }

            $schema[$group_key] = [
                'key'    => $group_key,
                'label'  => isset($group['label']) ? (string) $group['label'] : _iron_humanize($group_key),
                'fields' => $fields,

                // Interrupteur d'affichage de la section. `toggle_default`
                // vaut vrai : une section que le développeur vient d'ajouter
                // doit se voir, sans quoi il la croira cassée. C'est au client
                // de la masquer quand elle n'a plus lieu d'être.
                'toggle'         => !empty($group['toggle']),
                'toggle_default' => !isset($group['toggle_default']) || (bool) $group['toggle_default'],
                'label_toggle'   => isset($group['label_toggle'])
                    ? (string) $group['label_toggle']
                    : __('Afficher cette section sur le site', 'ironframe'),

                'storage' => $context,
            ];
        }

        return $schema;
    }
}

if (!function_exists('_iron_normalize_subfields')) {
    /**
     * Normalise les sous-champs d'un répétable.
     *
     * Ils ne portent aucune clé de stockage : leur valeur vit à l'intérieur de
     * celle du répétable, pas dans une ligne de `postmeta` ou `options`
     * distincte.
     *
     * Un répétable dans un répétable est refusé. Ce n'est pas une limite
     * technique insurmontable, c'est un choix : la gestion des index imbriqués
     * double la complexité du rendu, du JavaScript et de la sauvegarde, pour
     * un besoin qui ne s'est jamais présenté sur un site vitrine.
     *
     * @param mixed  $raw
     * @param string $template
     * @param string $parent_path Chemin du répétable, pour les messages.
     * @return array<string, array>
     */
    function _iron_normalize_subfields($raw, $template, $parent_path)
    {
        if (!is_array($raw)) {
            return [];
        }

        $fields = [];

        foreach ($raw as $key => $field) {

            if (!_iron_is_valid_key($key)) {
                _iron_schema_warning(
                    sprintf('identifiant de sous-champ invalide « %s » dans « %s ».', $key, $parent_path),
                    $template
                );
                continue;
            }

            if (!is_array($field) || empty($field['type'])) {
                _iron_schema_warning(
                    sprintf('le sous-champ « %s.%s » doit déclarer une clé `type`.', $parent_path, $key),
                    $template
                );
                continue;
            }

            if ('repeater' === $field['type']) {
                _iron_schema_warning(
                    sprintf('« %s.%s » : un répétable ne peut pas en contenir un autre.', $parent_path, $key),
                    $template
                );
                continue;
            }

            if (!iron_field_type_exists($field['type'])) {
                _iron_schema_warning(
                    sprintf('type inconnu « %s » sur le sous-champ « %s.%s ».', $field['type'], $parent_path, $key),
                    $template
                );
                continue;
            }

            if (!empty($field['required'])) {
                _iron_schema_warning(
                    sprintf('« %s.%s » : `required` n\'est pas géré sur un sous-champ de liste répétable, la clé est ignorée.', $parent_path, $key),
                    $template
                );
            }

            $type  = iron_field_type($field['type']);
            $extra = [];

            if ('select' === $field['type']) {
                $options = isset($field['options']) && is_array($field['options'])
                    ? $field['options']
                    : [];

                if (!$options) {
                    _iron_schema_warning(
                        sprintf('la liste de choix « %s.%s » doit déclarer une clé `options` non vide.', $parent_path, $key),
                        $template
                    );
                    continue;
                }

                $clean_options = [];

                foreach ($options as $option_key => $option_label) {
                    $clean_options[(string) $option_key] = (string) $option_label;
                }

                $extra = ['options' => $clean_options];
            }

            $fields[$key] = array_merge([
                'key'     => $key,
                'type'    => $field['type'],
                'label'   => isset($field['label']) ? (string) $field['label'] : _iron_humanize($key),
                'desc'    => isset($field['desc']) ? (string) $field['desc'] : '',
                'default' => array_key_exists('default', $field) ? $field['default'] : $type['default'],
            ], $extra);
        }

        return $fields;
    }
}

if (!function_exists('_iron_is_valid_key')) {
    /**
     * Un identifiant sert à construire une clé de meta et un attribut `name`
     * de formulaire : il doit rester strictement alphanumérique.
     *
     * @param mixed $key
     * @return bool
     */
    function _iron_is_valid_key($key)
    {
        return is_string($key) && (bool) preg_match('/^[a-z][a-z0-9_]*$/', $key);
    }
}

if (!function_exists('_iron_humanize')) {
    /**
     * Fabrique un libellé lisible à partir d'un identifiant.
     *
     * @param string $key
     * @return string
     */
    function _iron_humanize($key)
    {
        return ucfirst(str_replace('_', ' ', $key));
    }
}

if (!function_exists('_iron_schema_warning')) {
    /**
     * Signale une déclaration invalide, uniquement en développement.
     *
     * @param string $message
     * @param string $template
     * @return void
     */
    function _iron_schema_warning($message, $template)
    {
        _iron_debug_warning(sprintf('schéma de « %s » : %s', $template, $message));
    }
}
