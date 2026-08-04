<?php
/**
 * Listes répétables.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_test('Les lignes sont nettoyées et complétées', function () {

    $page  = iron_test_page();
    $field = iron_test_field('listing.rows');

    iron_save_raw_value($field, [
        ['name' => 'Audit', 'pic' => iron_test_attachment(), 'kind' => 'a'],
        ['name' => '<script>x</script>Conseil', 'pic' => 999999, 'url' => ['url' => 'javascript:alert(9)']],
    ], $page);

    $stock = get_post_meta($page, $field['meta_key'], true);

    iron_assert_same(2, count($stock), 'deux lignes enregistrées');
    iron_assert_same('name,pic,url,kind', implode(',', array_keys($stock[0])), 'sous-champs manquants complétés');
    iron_assert_same(iron_test_attachment(), $stock[0]['pic'], 'image valide conservée');
    iron_assert_same(0, $stock[1]['pic'], 'image inexistante ramenée à zéro');
    iron_assert_same('', $stock[1]['url']['url'], 'adresse hostile neutralisée');
    iron_assert_same('', $stock[1]['kind'], 'choix absent ramené au vide');
});

iron_test('Les lignes sont réindexées d\'après l\'ordre de soumission', function () {

    $page  = iron_test_page();
    $field = iron_test_field('listing.rows');

    // Index troués et désordonnés, comme après une suppression et un
    // déplacement dans le formulaire.
    iron_save_raw_value($field, [5 => ['name' => 'C'], 2 => ['name' => 'A'], 9 => ['name' => 'B']], $page);

    $stock = get_post_meta($page, $field['meta_key'], true);

    iron_assert_same('0,1,2', implode(',', array_keys($stock)), 'clés réindexées');
    iron_assert_same('CAB', $stock[0]['name'] . $stock[1]['name'] . $stock[2]['name'], 'ordre du formulaire respecté');
});

iron_test('Le plafond est appliqué à l\'enregistrement', function () {

    $page  = iron_test_page();
    $field = iron_test_field('listing.rows');

    $lignes = [];

    for ($i = 0; $i < 9; $i++) {
        $lignes[] = ['name' => 'L' . $i];
    }

    iron_save_raw_value($field, $lignes, $page);

    iron_assert_same(3, count(get_post_meta($page, $field['meta_key'], true)), 'tronqué au maximum déclaré');
});

iron_test('Les lignes exposées au template sont sûres', function () {

    $page = iron_test_page();

    iron_save_raw_value(iron_test_field('listing.rows'), [
        [
            'name' => '<script>x</script>Audit',
            'pic'  => iron_test_attachment(),
            'url'  => ['url' => 'https://exemple.fr', 'label' => 'Voir', 'target' => '_blank'],
            'kind' => 'b',
        ],
    ], $page);

    $lignes = iron_rows('listing.rows', $page);

    iron_assert_same(1, count($lignes), 'une ligne lue');
    iron_assert_same('xAudit', $lignes[0]['name'], 'accès direct déjà échappé');
    iron_assert_same('xAudit', iron_row($lignes[0], 'name'), 'iron_row équivalent');
    iron_assert_same('b', $lignes[0]['kind'], 'liste de choix dans une ligne');

    iron_assert_true(iron_row_has($lignes[0], 'pic'), 'image présente');
    iron_assert_true(iron_row_has($lignes[0], 'url'), 'lien présent');

    iron_assert_contains('rel="noopener noreferrer"', iron_row_link($lignes[0], 'url'), 'noopener sur le lien de ligne');

    iron_assert_same('', iron_row($lignes[0], 'inconnu'), 'sous-champ inconnu');
    iron_assert_same('', iron_row_image($lignes[0], 'name'), 'mauvais type de sous-champ');
    iron_assert_same('', iron_row(['name' => 'x'], 'name'), 'ligne ne provenant pas de iron_rows');
});

iron_test('Une liste vide renvoie un tableau vide', function () {

    $page  = iron_test_page();
    $field = iron_test_field('listing.rows');

    delete_post_meta($page, $field['meta_key']);

    iron_assert_same([], iron_rows('listing.rows', $page), 'aucune ligne');
    iron_assert_false(iron_has('listing.rows', $page), 'iron_has faux');
});
