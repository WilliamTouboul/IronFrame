<?php
/**
 * Client de mise à jour.
 *
 * WordPress ne sait interroger que son propre dépôt, qui n'héberge que des
 * thèmes gratuits. Ce module lui apprend à interroger le nôtre.
 *
 * Le mécanisme tient en une phrase : on demande à une URL « quelle est la
 * dernière version ? », et si la réponse est plus récente que ce qui est
 * installé, on l'annonce à WordPress, qui affiche la mise à jour comme
 * n'importe quelle autre.
 *
 * Le serveur doit répondre du JSON de cette forme :
 *
 *     {
 *       "version":      "1.1.0",
 *       "package":      "https://exemple.fr/ironframe-1.1.0.zip",
 *       "url":          "https://exemple.fr/journal-des-versions",
 *       "requires":     "6.0",
 *       "requires_php": "7.4"
 *     }
 *
 * Seuls `version` et `package` sont obligatoires. Un simple fichier statique
 * suffit pour commencer ; une plateforme de licences répondra la même chose,
 * en refusant les clés invalides. **Le code ci-dessous ne change pas entre les
 * deux** : seule l'URL diffère.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

/**
 * Adresse interrogée pour connaître la dernière version.
 *
 * Vide par défaut : sans cela, chaque site installé enverrait deux requêtes
 * par jour vers une URL qui n'existe pas. La fonctionnalité reste donc
 * dormante tant qu'elle n'est pas renseignée.
 *
 * Peut être définie dans `wp-config.php` pour un essai, ou inscrite en dur ici
 * au moment de la mise en vente.
 */
if (!defined('IRON_UPDATE_ENDPOINT')) {
    define('IRON_UPDATE_ENDPOINT', '');
}

/** Durée de mise en cache d'une réponse réussie. */
define('IRON_UPDATE_CACHE', 12 * HOUR_IN_SECONDS);

/** Durée d'attente après un échec, pour ne pas marteler un serveur en panne. */
define('IRON_UPDATE_RETRY', HOUR_IN_SECONDS);

if (!function_exists('iron_update_endpoint')) {
    /**
     * @return string Chaîne vide si les mises à jour ne sont pas configurées.
     */
    function iron_update_endpoint()
    {
        /**
         * Permet de pointer ailleurs — un serveur de test, par exemple.
         *
         * @param string $url
         */
        return (string) apply_filters('iron_update_endpoint', IRON_UPDATE_ENDPOINT);
    }
}

if (!function_exists('iron_update_slug')) {
    /**
     * Dossier du thème parent.
     *
     * On ne code pas « ironframe » en dur : le thème est revendu en marque
     * blanche, son dossier peut donc être renommé.
     *
     * @return string
     */
    function iron_update_slug()
    {
        return get_template();
    }
}

if (!function_exists('iron_installed_version')) {
    /**
     * @return string
     */
    function iron_installed_version()
    {
        $theme = wp_get_theme(iron_update_slug());

        return $theme->exists() ? (string) $theme->get('Version') : '';
    }
}

/* -------------------------------------------------------------------------- */
/* Interrogation du serveur                                                   */
/* -------------------------------------------------------------------------- */

if (!function_exists('iron_fetch_update_info')) {
    /**
     * Demande au serveur la dernière version disponible.
     *
     * Le résultat est mis en cache : le filtre appelant se déclenche à chaque
     * chargement de l'administration, pas question d'ouvrir une connexion à
     * chaque fois.
     *
     * @param bool $force Ignorer le cache.
     * @return array|null Charge utile validée, ou null.
     */
    function iron_fetch_update_info($force = false)
    {
        $endpoint = iron_update_endpoint();

        if ('' === $endpoint) {
            return null;
        }

        $cle = 'iron_update_info';

        if (!$force) {
            $cache = get_site_transient($cle);

            // 'ko' mémorise un échec : on ne réessaie pas avant l'expiration.
            if ('ko' === $cache) {
                return null;
            }

            if (is_array($cache)) {
                return $cache;
            }
        }

        /**
         * Arguments joints à la requête.
         *
         * C'est ici que viendra la clé de licence, le jour venu. Le reste du
         * module n'aura pas à bouger.
         *
         * @param array $args
         */
        $args = apply_filters('iron_update_request_args', [
            'version' => iron_installed_version(),
            'site'    => home_url('/'),
        ]);

        $reponse = wp_remote_get(
            add_query_arg($args, $endpoint),
            [
                'timeout' => 10,
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        if (is_wp_error($reponse) || 200 !== (int) wp_remote_retrieve_response_code($reponse)) {
            set_site_transient($cle, 'ko', IRON_UPDATE_RETRY);

            return null;
        }

        $donnees = json_decode(wp_remote_retrieve_body($reponse), true);
        $valide  = _iron_validate_update_payload($donnees);

        if (null === $valide) {
            set_site_transient($cle, 'ko', IRON_UPDATE_RETRY);

            return null;
        }

        set_site_transient($cle, $valide, IRON_UPDATE_CACHE);

        return $valide;
    }
}

if (!function_exists('_iron_validate_update_payload')) {
    /**
     * Vérifie ce que le serveur a répondu.
     *
     * Une réponse de mise à jour désigne un fichier que WordPress va
     * télécharger puis **déployer par-dessus le thème**. On ne fait donc
     * aucune confiance à son contenu.
     *
     * @param mixed $donnees
     * @return array|null
     */
    function _iron_validate_update_payload($donnees)
    {
        if (!is_array($donnees) || empty($donnees['version']) || empty($donnees['package'])) {
            return null;
        }

        $version = (string) $donnees['version'];

        // Un numéro de version, rien d'autre.
        if (!preg_match('/^[0-9]+(\.[0-9]+)*(-[a-z0-9.]+)?$/i', $version)) {
            return null;
        }

        $package = esc_url_raw((string) $donnees['package']);

        if ('' === $package) {
            return null;
        }

        /*
         * HTTPS obligatoire, sauf en local. Sur du HTTP, n'importe qui sur le
         * réseau pourrait substituer son propre zip — et ce zip est déployé
         * dans le thème, donc exécuté.
         */
        if (0 !== stripos($package, 'https://') && !_iron_is_local_environment()) {
            return null;
        }

        return [
            'version'      => $version,
            'package'      => $package,
            'url'          => isset($donnees['url']) ? esc_url_raw((string) $donnees['url']) : '',
            'requires'     => isset($donnees['requires']) ? (string) $donnees['requires'] : '',
            'requires_php' => isset($donnees['requires_php']) ? (string) $donnees['requires_php'] : '',
        ];
    }
}

if (!function_exists('_iron_is_local_environment')) {
    /**
     * @return bool
     */
    function _iron_is_local_environment()
    {
        return in_array(wp_get_environment_type(), ['local', 'development'], true);
    }
}

/* -------------------------------------------------------------------------- */
/* Déclaration à WordPress                                                    */
/* -------------------------------------------------------------------------- */

if (!function_exists('iron_inject_theme_update')) {
    /**
     * Ajoute notre thème au relevé des mises à jour.
     *
     * On renseigne aussi `no_update` quand tout est à jour : c'est ce qui
     * indique à WordPress que le thème est suivi, et lui évite d'afficher
     * « ce thème ne recevra pas de mises à jour ».
     *
     * @param mixed $transient
     * @return mixed
     */
    function iron_inject_theme_update($transient)
    {
        if (!is_object($transient)) {
            return $transient;
        }

        $info = iron_fetch_update_info();

        if (null === $info) {
            return $transient;
        }

        $slug    = iron_update_slug();
        $courante = iron_installed_version();

        if ('' === $courante) {
            return $transient;
        }

        $entree = [
            'theme'        => $slug,
            'new_version'  => $info['version'],
            'url'          => $info['url'],
            'package'      => $info['package'],
            'requires'     => $info['requires'],
            'requires_php' => $info['requires_php'],
        ];

        if (version_compare($info['version'], $courante, '>')) {
            $transient->response[$slug] = $entree;
            unset($transient->no_update[$slug]);
        } else {
            $transient->no_update[$slug] = $entree;
            unset($transient->response[$slug]);
        }

        return $transient;
    }
}
add_filter('site_transient_update_themes', 'iron_inject_theme_update');

if (!function_exists('iron_clear_update_cache')) {
    /**
     * Oublie la dernière réponse du serveur.
     *
     * Appelé après une mise à jour, et lorsqu'on force une vérification depuis
     * l'écran des mises à jour : sans cela, WordPress continuerait d'annoncer
     * une version qui vient d'être installée.
     *
     * @return void
     */
    function iron_clear_update_cache()
    {
        delete_site_transient('iron_update_info');
    }
}
add_action('upgrader_process_complete', 'iron_clear_update_cache');
add_action('load-update-core.php', 'iron_clear_update_cache');
