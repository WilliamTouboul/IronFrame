<?php
/**
 * Panneau de diagnostic dans la barre d'administration.
 *
 * Répond en deux secondes à la question la plus fréquente du développeur qui
 * reprend un projet Ironframe : « quel template sert cette page, quels champs
 * existent, et que contiennent-ils vraiment ? »
 *
 * N'existe que sous `WP_DEBUG` et pour un utilisateur capable de gérer les
 * options : aucune trace en production, aucune fuite de contenu.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_value_summary')) {
    /**
     * Résumé lisible d'une valeur de champ, quel que soit son type.
     *
     * @param mixed $value Valeur brute.
     * @param array $field Champ normalisé.
     * @param int   $length Longueur maximale du résumé.
     * @return string
     */
    function iron_value_summary($value, array $field, $length = 48)
    {
        if (!iron_value_is_filled($value, $field)) {
            return '(vide)';
        }

        switch ($field['type']) {

            case 'repeater':
                $count = is_array($value) ? count($value) : 0;

                return sprintf(_n('%d ligne', '%d lignes', $count, 'ironframe'), $count);

            case 'image':
                return sprintf('média #%d', absint($value));

            case 'link':
                $url = isset($value['url']) ? $value['url'] : '';

                return isset($value['label']) && '' !== $value['label']
                    ? $value['label'] . ' → ' . $url
                    : $url;

            default:
                $text = trim(preg_replace('/\s+/', ' ', (string) $value));

                return mb_strlen($text) > $length
                    ? mb_substr($text, 0, $length - 1) . '…'
                    : $text;
        }
    }
}

if (!function_exists('iron_debug_current_page')) {
    /**
     * La page concernée, en front comme sur l'écran d'édition.
     *
     * @return WP_Post|null
     */
    function iron_debug_current_page()
    {
        if (is_admin()) {
            $post = get_post();

            return ($post && 'page' === $post->post_type) ? $post : null;
        }

        if (!is_singular('page')) {
            return null;
        }

        $post = get_queried_object();

        return $post instanceof WP_Post ? $post : null;
    }
}

if (!function_exists('_iron_debug_relative_path')) {
    /**
     * Raccourcit un chemin absolu en « dossier-du-thème/chemin/relatif ».
     *
     * @param string $path
     * @return string
     */
    function _iron_debug_relative_path($path)
    {
        $real = realpath($path);

        if (!$real) {
            return (string) $path;
        }

        foreach (iron_theme_roots() as $root) {
            $root_real = realpath($root);

            if ($root_real && 0 === strpos($real, $root_real)) {
                return basename($root_real) . str_replace('\\', '/', substr($real, strlen($root_real)));
            }
        }

        return (string) $path;
    }
}

if (!function_exists('iron_debug_admin_bar')) {
    /**
     * @param WP_Admin_Bar $bar
     * @return void
     */
    function iron_debug_admin_bar($bar)
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG || !current_user_can('manage_options')) {
            return;
        }

        $post = iron_debug_current_page();

        if (!$post) {
            return;
        }

        $template = get_page_template_slug($post);
        $schema   = iron_get_post_schema($post);

        $bar->add_node([
            'id'    => 'iron-debug',
            'title' => 'Ironframe : ' . ('' !== $template ? $template : 'aucun template'),
        ]);

        if (!$schema) {
            $bar->add_node([
                'id'     => 'iron-debug-empty',
                'parent' => 'iron-debug',
                'title'  => 'Aucun schéma de champs pour ce template',
            ]);

            return;
        }

        // Quel fichier sert réellement — la question qui se pose dès qu'un
        // thème enfant redéfinit un gabarit du moteur.
        $fichier = iron_schema_file_for_template($template);

        if ('' !== $fichier) {
            $bar->add_node([
                'id'     => 'iron-debug-source',
                'parent' => 'iron-debug',
                'title'  => 'Schéma lu dans : ' . _iron_debug_relative_path($fichier),
            ]);
        }

        foreach ($schema as $group_key => $group) {

            $active = iron_is_enabled($group_key, $post);

            $bar->add_node([
                'id'     => 'iron-debug-' . $group_key,
                'parent' => 'iron-debug',
                'title'  => $group['label'] . ($active ? '' : ' — section masquée'),
            ]);

            foreach ($group['fields'] as $field) {
                $bar->add_node([
                    'id'     => 'iron-debug-' . $group_key . '-' . $field['key'],
                    'parent' => 'iron-debug-' . $group_key,
                    'title'  => sprintf(
                        '%s (%s) = %s',
                        $field['path'],
                        $field['type'],
                        // Valeur brute et non échappée : l'interrupteur de
                        // section ne doit pas masquer ce qu'on vient
                        // diagnostiquer.
                        iron_value_summary(iron_get_raw_value($field, $post), $field)
                    ),
                ]);
            }
        }
    }
}
add_action('admin_bar_menu', 'iron_debug_admin_bar', 100);
