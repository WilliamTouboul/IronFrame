<?php
/**
 * Brique 6 — Découverte des schémas d'options globales.
 *
 * Même convention que pour les pages : le développeur pose un fichier, il n'a
 * rien à enregistrer. Tous les `options/*.fields.php` sont fusionnés en un seul
 * schéma, ce qui laisse le choix de tout déclarer dans un fichier ou d'éclater
 * par thématique.
 *
 * Les identifiants de groupe doivent donc être uniques d'un fichier à l'autre :
 * c'est le prix à payer pour que les chemins restent `groupe.champ`, identiques
 * à ceux des pages.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_get_options_schema')) {
    /**
     * Schéma normalisé de toutes les options globales.
     *
     * @return array<string, array>
     */
    function iron_get_options_schema()
    {
        static $schema = null;

        if (null !== $schema) {
            return $schema;
        }

        $files = glob(IRON_PATH . '/options/*.fields.php');

        if (!$files) {
            $schema = [];

            return $schema;
        }

        // `glob()` trie : l'ordre des sections dans l'écran d'admin est donc
        // celui des noms de fichiers, et il est stable d'un serveur à l'autre.
        $raw = [];

        foreach ($files as $file) {
            $part = require $file;

            if (!is_array($part)) {
                _iron_schema_warning(
                    sprintf('le fichier doit retourner un tableau (%s reçu).', gettype($part)),
                    'options/' . basename($file)
                );
                continue;
            }

            foreach ($part as $group_key => $group) {
                if (isset($raw[$group_key])) {
                    _iron_schema_warning(
                        sprintf('le groupe « %s » est déjà déclaré dans un autre fichier, celui-ci est ignoré.', $group_key),
                        'options/' . basename($file)
                    );
                    continue;
                }

                $raw[$group_key] = $group;
            }
        }

        $schema = _iron_normalize_schema($raw, 'options', 'option');

        return $schema;
    }
}

if (!function_exists('iron_get_option_definition')) {
    /**
     * Définition normalisée d'une option, à partir de son chemin.
     *
     * @param string $path Chemin `groupe.champ`.
     * @return array|null
     */
    function iron_get_option_definition($path)
    {
        $flat = iron_flatten_schema(iron_get_options_schema());

        return isset($flat[$path]) ? $flat[$path] : null;
    }
}
