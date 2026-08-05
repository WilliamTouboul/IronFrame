<?php
/**
 * Découverte, normalisation et validation des schémas.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_test('Le schéma est découvert par le nom du fichier', function () {

    $schema = iron_get_post_schema(iron_test_page());

    iron_assert_same(3, count($schema), 'trois groupes découverts');
    iron_assert_same('basic,toggled,listing', implode(',', array_keys($schema)), 'ordre de déclaration respecté');
    iron_assert_same('Champs simples', $schema['basic']['label'], 'libellé du groupe');
});

iron_test('Les champs sont normalisés', function () {

    $field = iron_test_field('basic.title');

    iron_assert_same('text', $field['type'], 'type');
    iron_assert_same('basic', $field['group'], 'groupe');
    iron_assert_same('basic.title', $field['path'], 'chemin');
    iron_assert_same('_iron_basic_title', $field['meta_key'], 'clé de meta');
    iron_assert_true($field['required'], 'obligatoire');
    iron_assert_false(iron_test_field('basic.text')['required'], 'optionnel par défaut');
});

iron_test('Un libellé absent est déduit de l\'identifiant', function () {

    $schema = _iron_normalize_schema(
        ['mon_groupe' => ['fields' => ['mon_champ' => ['type' => 'text']]]],
        'test'
    );

    iron_assert_same('Mon groupe', $schema['mon_groupe']['label'], 'libellé du groupe');
    iron_assert_same('Mon champ', $schema['mon_groupe']['fields']['mon_champ']['label'], 'libellé du champ');
});

iron_test('Une déclaration invalide est ignorée, jamais fatale', function () {

    $schema = _iron_normalize_schema([
        'Majuscule'   => ['fields' => ['a' => ['type' => 'text']]],
        '2_chiffre'   => ['fields' => ['a' => ['type' => 'text']]],
        'sans_fields' => ['label' => 'Rien'],
        'bon' => [
            'fields' => [
                'valide'      => ['type' => 'text'],
                'sans_type'   => ['label' => 'Oups'],
                'type_inconnu' => ['type' => 'nawak'],
                'Invalide'    => ['type' => 'text'],
            ],
        ],
    ], 'test');

    iron_assert_same(1, count($schema), 'seul le groupe valide subsiste');
    iron_assert_same(1, count($schema['bon']['fields']), 'seul le champ valide subsiste');
    iron_assert_true(isset($schema['bon']['fields']['valide']), 'le champ valide est conservé');
});

iron_test('Les clés de stockage dépendent du contexte', function () {

    $page = _iron_normalize_schema(['g' => ['fields' => ['c' => ['type' => 'text']]]], 'test', 'post');
    $glob = _iron_normalize_schema(['g' => ['fields' => ['c' => ['type' => 'text']]]], 'test', 'option');

    iron_assert_same('_iron_g_c', $page['g']['fields']['c']['meta_key'], 'clé de meta pour une page');
    iron_assert_false(isset($page['g']['fields']['c']['option_name']), 'pas de nom d\'option parasite');

    iron_assert_same('iron_opt_g_c', $glob['g']['fields']['c']['option_name'], 'nom d\'option pour un réglage');
    iron_assert_false(isset($glob['g']['fields']['c']['meta_key']), 'pas de clé de meta parasite');
});

iron_test('Les clés d\'interrupteur ne peuvent pas entrer en collision', function () {

    // Sans le tiret, un groupe « hero_title » et un champ « hero.title »
    // produiraient exactement la même clé.
    iron_assert_same('_iron_hero_title-on', iron_group_toggle_key('hero_title'), 'clé de l\'interrupteur');
    iron_assert_same('_iron_hero_title', iron_meta_key('hero', 'title'), 'clé du champ');
    iron_assert_true(iron_group_toggle_key('hero_title') !== iron_meta_key('hero', 'title'), 'les deux diffèrent');
});

iron_test('Le chemin du schéma reste dans le thème', function () {

    iron_assert_same('', iron_schema_file_for_template('../../../wp-config.php'), 'remontée de dossier refusée');
    iron_assert_same('', iron_schema_file_for_template('default'), 'template par défaut sans schéma');
    iron_assert_same('', iron_schema_file_for_template(''), 'template vide');
});

iron_test('Un répétable ne peut pas en contenir un autre', function () {

    $schema = _iron_normalize_schema([
        'g' => [
            'fields' => [
                'liste' => [
                    'type'   => 'repeater',
                    'fields' => [
                        'ok'      => ['type' => 'text'],
                        'imbrique' => ['type' => 'repeater', 'fields' => ['x' => ['type' => 'text']]],
                    ],
                ],
            ],
        ],
    ], 'test');

    $sous_champs = $schema['g']['fields']['liste']['fields'];

    iron_assert_same(1, count($sous_champs), 'le répétable imbriqué est écarté');
    iron_assert_true(isset($sous_champs['ok']), 'le sous-champ valide est conservé');
});

iron_test('Tous les gabarits livrés ont un schéma valide', function () {

    $fichiers = glob(IRON_PATH . '/pages/*.fields.php');

    iron_assert_true(count($fichiers) >= 4, 'le jeu de départ est présent');

    foreach ($fichiers as $fichier) {

        $nom      = basename($fichier, '.fields.php');
        $template = 'pages/' . $nom . '.php';

        iron_assert_true(file_exists(IRON_PATH . '/' . $template), sprintf('%s : le gabarit existe', $nom));

        $schema = iron_get_schema($template);

        iron_assert_true(count($schema) > 0, sprintf('%s : au moins un groupe', $nom));

        foreach ($schema as $groupe) {
            iron_assert_true(count($groupe['fields']) > 0, sprintf('%s : le groupe « %s » a des champs', $nom, $groupe['key']));
        }
    }
});

iron_test('Une liste de choix sans options est refusée', function () {

    $schema = _iron_normalize_schema([
        'g' => [
            'fields' => [
                'sans_options' => ['type' => 'select'],
                'avec_options' => ['type' => 'select', 'options' => ['a' => 'A']],
            ],
        ],
    ], 'test');

    iron_assert_same(1, count($schema['g']['fields']), 'le champ sans options est écarté');
    iron_assert_same(['a' => 'A'], $schema['g']['fields']['avec_options']['options'], 'options normalisées');
});
