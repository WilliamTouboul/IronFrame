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

        $relative = preg_replace('/\.php$/', '.fields.php', $template);
        $path     = IRON_PATH . '/' . ltrim($relative, '/');

        if (!file_exists($path)) {
            return '';
        }

        // Le nom du template vient d'une meta, donc potentiellement d'une
        // écriture directe en base. On vérifie que le fichier résolu est bien
        // à l'intérieur du thème.
        $real  = realpath($path);
        $theme = realpath(IRON_PATH);

        if (!$real || !$theme || 0 !== strpos($real, $theme)) {
            return '';
        }

        return $real;
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
     * @param string $template
     * @return array<string, array>
     */
    function _iron_normalize_schema(array $raw, $template)
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

                $fields[$field_key] = [
                    'key'      => $field_key,
                    'group'    => $group_key,
                    'path'     => $group_key . '.' . $field_key,
                    'type'     => $field['type'],
                    'label'    => isset($field['label']) ? (string) $field['label'] : _iron_humanize($field_key),
                    'desc'     => isset($field['desc']) ? (string) $field['desc'] : '',
                    'default'  => array_key_exists('default', $field) ? $field['default'] : $type['default'],
                    'meta_key' => iron_meta_key($group_key, $field_key),
                ];
            }

            if (empty($fields)) {
                continue;
            }

            $schema[$group_key] = [
                'key'    => $group_key,
                'label'  => isset($group['label']) ? (string) $group['label'] : _iron_humanize($group_key),
                'fields' => $fields,
            ];
        }

        return $schema;
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
