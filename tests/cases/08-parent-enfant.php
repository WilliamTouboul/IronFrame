<?php
/**
 * Séparation moteur / projet : résolution des fichiers entre le thème parent
 * et le thème enfant.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

/**
 * Exécute un test avec une racine supplémentaire, prioritaire, simulant un
 * thème enfant.
 *
 * @param callable $body
 * @return void
 */
function iron_test_avec_enfant(callable $body)
{
    $faux = IRON_PATH . '/tests/fixtures/faux-enfant';

    $filtre = function ($roots) use ($faux) {
        array_unshift($roots, $faux);

        return $roots;
    };

    add_filter('iron_theme_roots', $filtre);

    try {
        $body($faux);
    } finally {
        remove_filter('iron_theme_roots', $filtre);
    }
}

iron_test('Les racines sont ordonnées, projet puis moteur', function () {

    // Le test ne présume pas de la configuration : il doit passer avec ou sans
    // thème enfant actif.
    $roots  = iron_theme_roots();
    $enfant = get_stylesheet_directory() !== get_template_directory();

    iron_assert_true(count($roots) > 0, 'au moins une racine');
    iron_assert_same($enfant ? 2 : 1, count($roots), 'une racine par thème actif');
    iron_assert_same(realpath(IRON_PATH), realpath(end($roots)), 'le moteur est toujours en dernier');

    if ($enfant) {
        iron_assert_same(realpath(get_stylesheet_directory()), realpath($roots[0]), 'le projet passe en premier');
    }

    $reels = array_map('realpath', $roots);

    iron_assert_same(count($reels), count(array_unique($reels)), 'aucune racine en double');
});

iron_test('Un fichier du projet masque celui du moteur', function () {

    $moteur = iron_locate('pages/accueil.fields.php');

    iron_assert_true('' !== $moteur, 'le moteur fournit bien ce fichier');

    iron_test_avec_enfant(function ($faux) use ($moteur) {

        $resolu = iron_locate('pages/accueil.fields.php');

        iron_assert_true($resolu !== $moteur, 'le fichier résolu a changé');
        iron_assert_contains('faux-enfant', $resolu, 'il vient du thème enfant');

        // Ce que contient réellement le fichier résolu.
        $schema = _iron_normalize_schema(require $resolu, 'test');

        iron_assert_true(isset($schema['depuis_lenfant']), 'c\'est bien le schéma de l\'enfant');
    });

    iron_assert_same($moteur, iron_locate('pages/accueil.fields.php'), 'retour au moteur après coup');
});

iron_test('Un fichier absent de l\'enfant reste servi par le moteur', function () {

    iron_test_avec_enfant(function () {

        $contact = iron_locate('pages/contact.fields.php');

        iron_assert_true('' !== $contact, 'le fichier est trouvé');
        iron_assert_false(false !== strpos($contact, 'faux-enfant'), 'il vient du moteur');
    });
});

iron_test('Les listes fusionnent les deux racines', function () {

    $avant = iron_glob('options/*.fields.php');

    iron_test_avec_enfant(function () use ($avant) {

        $apres = iron_glob('options/*.fields.php');

        iron_assert_same(count($avant) + 1, count($apres), 'le fichier propre au projet s\'ajoute');

        $noms = array_map('basename', $apres);

        iron_assert_true(in_array('zzz-projet.fields.php', $noms, true), 'le fichier du projet est présent');
        iron_assert_true(in_array('site.fields.php', $noms, true), 'ceux du moteur restent présents');
        iron_assert_same(count($noms), count(array_unique($noms)), 'aucun doublon');
    });
});

iron_test('Une liste ne garde qu\'une version de chaque nom', function () {

    iron_test_avec_enfant(function () {

        $pages = iron_glob('pages/*.fields.php');
        $noms  = array_map('basename', $pages);

        iron_assert_same(count($noms), count(array_unique($noms)), 'aucun doublon');

        foreach ($pages as $chemin) {
            if ('accueil.fields.php' === basename($chemin)) {
                iron_assert_contains('faux-enfant', $chemin, 'accueil vient de l\'enfant');
            }
        }
    });
});

iron_test('Le confinement au thème est respecté', function () {

    iron_assert_true(iron_path_is_inside_theme(IRON_PATH . '/functions.php'), 'un fichier du moteur');
    iron_assert_false(iron_path_is_inside_theme(ABSPATH . 'wp-config.php'), 'un fichier hors du thème');
    iron_assert_false(iron_path_is_inside_theme(IRON_PATH . '/nexiste-pas.php'), 'un fichier inexistant');

    iron_assert_same('', iron_schema_file_for_template('../../../wp-config.php'), 'remontée de dossier refusée');
});

iron_test('Les adresses publiques suivent la même priorité', function () {

    $uri = iron_locate_uri('style.css');

    iron_assert_true('' !== $uri, 'le style du moteur est trouvé');
    iron_assert_contains('ironframe', $uri, 'l\'adresse pointe vers le thème');
    iron_assert_same('', iron_locate_uri('nexiste-pas.css'), 'un fichier absent ne renvoie rien');
});
