<?php
/**
 * Client de mise à jour.
 *
 * Aucun serveur n'est nécessaire : `pre_http_request` permet de répondre à la
 * place du réseau, ce qui rend les cas déterministes et rapides.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

/**
 * Exécute un test en simulant la réponse du serveur de mises à jour.
 *
 * @param mixed    $corps  Corps de la réponse, ou tableau à encoder en JSON.
 * @param callable $body
 * @param int      $code   Code HTTP simulé.
 * @return void
 */
function iron_test_avec_serveur($corps, callable $body, $code = 200)
{
    $endpoint = function () {
        return 'https://exemple.invalid/api/version';
    };

    $reponse = function () use ($corps, $code) {
        return [
            'headers'  => [],
            'body'     => is_string($corps) ? $corps : wp_json_encode($corps),
            'response' => ['code' => $code, 'message' => 'OK'],
            'cookies'  => [],
            'filename' => null,
        ];
    };

    add_filter('iron_update_endpoint', $endpoint);
    add_filter('pre_http_request', $reponse);

    delete_site_transient('iron_update_info');

    try {
        $body();
    } finally {
        remove_filter('iron_update_endpoint', $endpoint);
        remove_filter('pre_http_request', $reponse);
        delete_site_transient('iron_update_info');
    }
}

/**
 * Un relevé de mises à jour vide, tel que WordPress le manipule.
 *
 * @return stdClass
 */
function iron_test_transient()
{
    $t             = new stdClass();
    $t->response   = [];
    $t->no_update  = [];
    $t->checked    = [];

    return $t;
}

iron_test('Sans adresse configurée, aucune requête n\'est faite', function () {

    iron_assert_same('', iron_update_endpoint(), 'adresse vide par défaut');
    iron_assert_same(null, iron_fetch_update_info(), 'aucune information');

    $transient = iron_inject_theme_update(iron_test_transient());

    iron_assert_same([], $transient->response, 'relevé intact');
    iron_assert_same([], $transient->no_update, 'aucune entrée ajoutée');
});

iron_test('Une version plus récente est annoncée à WordPress', function () {

    iron_test_avec_serveur([
        'version'  => '99.0.0',
        'package'  => 'https://exemple.invalid/ironframe-99.0.0.zip',
        'url'      => 'https://exemple.invalid/journal',
        'requires' => '6.0',
    ], function () {

        $transient = iron_inject_theme_update(iron_test_transient());
        $slug      = iron_update_slug();

        iron_assert_true(isset($transient->response[$slug]), 'la mise à jour est annoncée');
        iron_assert_same('99.0.0', $transient->response[$slug]['new_version'], 'numéro de version');
        iron_assert_same('https://exemple.invalid/ironframe-99.0.0.zip', $transient->response[$slug]['package'], 'archive');
        iron_assert_false(isset($transient->no_update[$slug]), 'pas dans les deux listes à la fois');
    });
});

iron_test('Une version identique ou plus ancienne ne déclenche rien', function () {

    iron_test_avec_serveur([
        'version' => '0.0.1',
        'package' => 'https://exemple.invalid/vieux.zip',
    ], function () {

        $transient = iron_inject_theme_update(iron_test_transient());
        $slug      = iron_update_slug();

        iron_assert_false(isset($transient->response[$slug]), 'aucune mise à jour annoncée');
        iron_assert_true(isset($transient->no_update[$slug]), 'le thème est déclaré suivi');
    });
});

iron_test('Une réponse malformée est écartée', function () {

    $cas = [
        'JSON invalide'      => 'ceci n\'est pas du json',
        'tableau vide'       => [],
        'sans archive'       => ['version' => '9.9.9'],
        'sans version'       => ['package' => 'https://exemple.invalid/x.zip'],
        'version fantaisiste' => ['version' => '<script>', 'package' => 'https://exemple.invalid/x.zip'],
    ];

    foreach ($cas as $etiquette => $corps) {
        iron_test_avec_serveur($corps, function () use ($etiquette) {
            iron_assert_same(null, iron_fetch_update_info(), $etiquette);
        });
    }
});

iron_test('Une archive en HTTP est refusée hors environnement local', function () {

    $environnement = function () {
        return 'production';
    };

    add_filter('pre_option_WP_ENVIRONMENT_TYPE', $environnement);

    // `wp_get_environment_type()` lit la constante avant l'option : on teste
    // donc la validation directement, sans dépendre de l'environnement réel.
    $charge = [
        'version' => '99.0.0',
        'package' => 'http://exemple.invalid/ironframe.zip',
    ];

    $valide = _iron_validate_update_payload($charge);

    if (_iron_is_local_environment()) {
        iron_assert_true(is_array($valide), 'acceptée en local, ce qui est voulu');
    } else {
        iron_assert_same(null, $valide, 'refusée en production');
    }

    // La règle qui compte partout : HTTPS passe toujours.
    $charge['package'] = 'https://exemple.invalid/ironframe.zip';

    iron_assert_true(is_array(_iron_validate_update_payload($charge)), 'HTTPS accepté');

    remove_filter('pre_option_WP_ENVIRONMENT_TYPE', $environnement);
});

iron_test('Une erreur du serveur ne casse rien', function () {

    iron_test_avec_serveur(['version' => '99.0.0', 'package' => 'https://exemple.invalid/x.zip'], function () {

        iron_assert_same(null, iron_fetch_update_info(), 'réponse 500 ignorée');

        $transient = iron_inject_theme_update(iron_test_transient());

        iron_assert_same([], $transient->response, 'relevé intact');
    }, 500);
});

iron_test('La réponse est mise en cache', function () {

    iron_test_avec_serveur([
        'version' => '99.0.0',
        'package' => 'https://exemple.invalid/x.zip',
    ], function () {

        iron_fetch_update_info();

        $cache = get_site_transient('iron_update_info');

        iron_assert_true(is_array($cache), 'la réponse est mémorisée');
        iron_assert_same('99.0.0', $cache['version'], 'contenu du cache');

        iron_clear_update_cache();

        iron_assert_false((bool) get_site_transient('iron_update_info'), 'le cache se vide');
    });
});

iron_test('Les arguments de la requête sont extensibles', function () {

    $capture = null;

    $espion = function ($preempt, $args, $url) use (&$capture) {
        $capture = $url;

        return [
            'headers'  => [],
            'body'     => wp_json_encode(['version' => '99.0.0', 'package' => 'https://exemple.invalid/x.zip']),
            'response' => ['code' => 200, 'message' => 'OK'],
            'cookies'  => [],
            'filename' => null,
        ];
    };

    $endpoint = function () {
        return 'https://exemple.invalid/api/version';
    };

    $cle = function ($args) {
        $args['licence'] = 'ABC-123';

        return $args;
    };

    add_filter('iron_update_endpoint', $endpoint);
    add_filter('iron_update_request_args', $cle);
    add_filter('pre_http_request', $espion, 10, 3);

    delete_site_transient('iron_update_info');
    iron_fetch_update_info(true);

    iron_assert_contains('licence=ABC-123', (string) $capture, 'la clé de licence voyage avec la requête');
    iron_assert_contains('version=', (string) $capture, 'la version installée aussi');

    remove_filter('iron_update_endpoint', $endpoint);
    remove_filter('iron_update_request_args', $cle);
    remove_filter('pre_http_request', $espion, 10);
    delete_site_transient('iron_update_info');
});

iron_test('Le thème visé est le moteur, quel que soit son dossier', function () {

    iron_assert_same(get_template(), iron_update_slug(), 'c\'est le thème parent');
    iron_assert_true('' !== iron_installed_version(), 'sa version est lisible');
});
