<?php
/**
 * Résolution des chemins entre le thème parent et le thème enfant.
 *
 * Ironframe se vend comme un **moteur** que l'on met à jour, et se personnalise
 * dans un **thème enfant** que l'on n'écrase jamais. Toute la mécanique tient
 * dans ce fichier : partout ailleurs, on demande un fichier par son chemin
 * relatif et on obtient celui de l'enfant s'il existe, sinon celui du parent.
 *
 * Sans thème enfant, les deux racines sont identiques et le comportement est
 * exactement celui d'avant cette séparation.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_theme_roots')) {
    /**
     * Les racines à consulter, dans l'ordre de priorité.
     *
     * Volontairement sans cache : le calcul est trivial, et une valeur figée
     * empêcherait la suite de tests d'injecter une racine factice.
     *
     * @return string[]
     */
    function iron_theme_roots()
    {
        $roots  = [];
        $child  = get_stylesheet_directory();
        $parent = get_template_directory();

        if ($child !== $parent) {
            $roots[] = $child;
        }

        $roots[] = $parent;

        /**
         * Permet d'ajouter une racine de recherche.
         *
         * @param string[] $roots Chemins absolus, du plus prioritaire au moins.
         */
        return apply_filters('iron_theme_roots', $roots);
    }
}

if (!function_exists('iron_locate')) {
    /**
     * Chemin absolu d'un fichier, l'enfant l'emportant sur le parent.
     *
     * @param string $relative Chemin relatif à la racine du thème.
     * @return string Chaîne vide si le fichier n'existe nulle part.
     */
    function iron_locate($relative)
    {
        $relative = ltrim((string) $relative, '/');

        foreach (iron_theme_roots() as $root) {
            $path = $root . '/' . $relative;

            if (file_exists($path)) {
                return $path;
            }
        }

        return '';
    }
}

if (!function_exists('iron_locate_uri')) {
    /**
     * Adresse publique d'un fichier, avec la même priorité.
     *
     * @param string $relative
     * @return string Chaîne vide si le fichier n'existe nulle part.
     */
    function iron_locate_uri($relative)
    {
        $relative = ltrim((string) $relative, '/');

        if (get_stylesheet_directory() !== get_template_directory()
            && file_exists(get_stylesheet_directory() . '/' . $relative)
        ) {
            return get_stylesheet_directory_uri() . '/' . $relative;
        }

        if (file_exists(get_template_directory() . '/' . $relative)) {
            return get_template_directory_uri() . '/' . $relative;
        }

        return '';
    }
}

if (!function_exists('iron_glob')) {
    /**
     * Fichiers correspondant à un motif, fusionnés entre les racines.
     *
     * Un fichier de l'enfant masque celui du parent qui porte le même nom :
     * c'est ce qui permet de redéfinir un gabarit livré sans le modifier.
     *
     * @param string $pattern Motif relatif, par exemple `pages/*.fields.php`.
     * @return string[] Chemins absolus.
     */
    function iron_glob($pattern)
    {
        $pattern = ltrim((string) $pattern, '/');
        $trouves = [];

        foreach (iron_theme_roots() as $root) {
            foreach ((array) glob($root . '/' . $pattern) as $fichier) {
                $nom = basename($fichier);

                // Première racine trouvée = celle qui gagne.
                if (!isset($trouves[$nom])) {
                    $trouves[$nom] = $fichier;
                }
            }
        }

        ksort($trouves);

        return array_values($trouves);
    }
}

if (!function_exists('iron_path_is_inside_theme')) {
    /**
     * Le chemin résolu est-il bien à l'intérieur d'une des racines ?
     *
     * Un nom de gabarit provient d'une meta, donc potentiellement d'une
     * écriture directe en base : on refuse tout ce qui sortirait du thème.
     *
     * @param string $path
     * @return bool
     */
    function iron_path_is_inside_theme($path)
    {
        $real = realpath($path);

        if (!$real) {
            return false;
        }

        foreach (iron_theme_roots() as $root) {
            $root_real = realpath($root);

            if ($root_real && 0 === strpos($real, $root_real)) {
                return true;
            }
        }

        return false;
    }
}
