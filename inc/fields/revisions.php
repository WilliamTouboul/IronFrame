<?php
/**
 * Révisions des valeurs de champs.
 *
 * Sans ce module, un client qui supprime cinq lignes d'une liste répétable et
 * enregistre les perd définitivement : les révisions natives de WordPress
 * portent sur le titre et le contenu, jamais sur les métadonnées.
 *
 * Depuis WordPress 6.4, le core sait révisionner des metas. Trois mécanismes
 * sont branchés par défaut et font tout le travail :
 *
 *   - `wp_save_revisioned_meta_fields`          copie les metas dans la révision
 *   - `wp_restore_post_revision_meta`           les restaure
 *   - `wp_check_revisioned_meta_fields_have_changed`
 *         force la création d'une révision quand SEULE une meta a changé —
 *         indispensable ici, puisque l'éditeur est retiré des pages et que
 *         `post_content` ne bouge donc jamais.
 *
 * Il suffit de leur déclarer nos clés. On ne réimplémente rien.
 *
 * Le client ne voit pas la boîte des révisions (retirée dans `inc/client/`) :
 * c'est le développeur ou l'administrateur qui restaure, à la demande.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_revisioned_meta_keys')) {
    /**
     * Toutes les clés de meta écrites par le thème sur une page.
     *
     * Les schémas sont découverts à la demande, template par template. Les
     * révisions, elles, ont besoin de la liste complète en amont : on parcourt
     * donc tous les schémas de `pages/`.
     *
     * @return string[]
     */
    function iron_revisioned_meta_keys()
    {
        static $keys = null;

        if (null !== $keys) {
            return $keys;
        }

        $keys = [];

        foreach (iron_glob('pages/*.fields.php') as $file) {

            $template = 'pages/' . preg_replace('/\.fields\.php$/', '.php', basename($file));

            foreach (iron_get_schema($template) as $group_key => $group) {

                if (!empty($group['toggle'])) {
                    $keys[] = iron_group_toggle_key($group_key);
                }

                foreach ($group['fields'] as $field) {
                    $keys[] = $field['meta_key'];
                }
            }
        }

        /**
         * Permet d'ajouter des clés à révisionner.
         *
         * Utile si les templates sont rangés ailleurs que dans `pages/`, ou
         * pour révisionner une meta gérée hors d'Ironframe.
         *
         * @param string[] $keys
         */
        $keys = apply_filters('iron_revisioned_meta_keys', array_values(array_unique($keys)));

        return $keys;
    }
}

if (!function_exists('iron_declare_revisioned_meta')) {
    /**
     * Déclare nos clés auprès du mécanisme natif.
     *
     * @param array  $keys
     * @param string $post_type
     * @return array
     */
    function iron_declare_revisioned_meta($keys, $post_type)
    {
        if ('page' !== $post_type) {
            return $keys;
        }

        return array_values(array_unique(array_merge((array) $keys, iron_revisioned_meta_keys())));
    }
}
add_filter('wp_post_revision_meta_keys', 'iron_declare_revisioned_meta', 10, 2);
