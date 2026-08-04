<?php
/**
 * Nettoyage à l'écriture et échappement à la sortie, type par type.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_test('Le texte est nettoyé et échappé', function () {

    $field = iron_test_field('basic.title');

    iron_assert_same('alert(1)Bonjour', iron_sanitize_field_value('<script>alert(1)</script>Bonjour', $field), 'balise script retirée');
    iron_assert_same('Bonjour', iron_sanitize_field_value('  Bonjour  ', $field), 'espaces retirés');
    iron_assert_same('&lt;b&gt;', iron_escape_text('<b>'), 'échappement en sortie');
});

iron_test('Le texte long conserve les retours à la ligne', function () {

    $field = iron_test_field('basic.text');
    $brut  = iron_sanitize_field_value("Ligne un\nLigne deux", $field);

    iron_assert_same("Ligne un\nLigne deux", $brut, 'retours conservés en base');
    iron_assert_contains('<br />', iron_escape_textarea($brut), 'convertis en sortie');
    iron_assert_not_contains('<script', iron_sanitize_field_value("<script>x</script>a", $field), 'HTML retiré');
});

iron_test('Une image doit pointer vers une pièce jointe existante', function () {

    $field = iron_test_field('basic.image');

    iron_assert_same(iron_test_attachment(), iron_sanitize_field_value(iron_test_attachment(), $field), 'identifiant valide conservé');
    iron_assert_same(0, iron_sanitize_field_value(999999, $field), 'identifiant inexistant ramené à zéro');
    iron_assert_same(0, iron_sanitize_field_value(iron_test_page(), $field), 'une page n\'est pas une pièce jointe');
    iron_assert_same(0, iron_sanitize_field_value('abc', $field), 'valeur non numérique');
});

iron_test('Un lien filtre ses protocoles et sa cible', function () {

    $field = iron_test_field('basic.cta');

    $propre = iron_sanitize_field_value([
        'url'    => 'https://exemple.fr/page?a=1&b=2',
        'label'  => '<b>Voir</b>',
        'target' => '_blank',
    ], $field);

    iron_assert_same('https://exemple.fr/page?a=1&b=2', $propre['url'], 'adresse conservée');
    iron_assert_same('Voir', $propre['label'], 'HTML retiré du libellé');
    iron_assert_same('_blank', $propre['target'], 'cible conservée');

    $hostile = iron_sanitize_field_value(['url' => 'javascript:alert(9)', 'target' => 'nawak'], $field);

    iron_assert_same('', $hostile['url'], 'protocole javascript neutralisé');
    iron_assert_same('_self', $hostile['target'], 'cible inconnue ramenée au défaut');
});

iron_test('Une liste de choix n\'accepte que ses propres clés', function () {

    $field = iron_test_field('basic.align');

    iron_assert_same('right', iron_sanitize_field_value('right', $field), 'clé déclarée acceptée');
    iron_assert_same('', iron_sanitize_field_value('nawak', $field), 'clé inconnue refusée');
    iron_assert_same('', iron_sanitize_field_value(['tableau'], $field), 'valeur non scalaire refusée');
});

iron_test('Le vide est correctement détecté selon le type', function () {

    iron_assert_false(iron_value_is_filled('', iron_test_field('basic.title')), 'texte vide');
    iron_assert_true(iron_value_is_filled('a', iron_test_field('basic.title')), 'texte rempli');
    iron_assert_false(iron_value_is_filled(0, iron_test_field('basic.image')), 'image absente');
    iron_assert_false(iron_value_is_filled(['url' => '', 'label' => 'x'], iron_test_field('basic.cta')), 'lien sans adresse');
    iron_assert_true(iron_value_is_filled(['url' => 'https://a.fr'], iron_test_field('basic.cta')), 'lien avec adresse');
    iron_assert_false(iron_value_is_filled([], iron_test_field('listing.rows')), 'liste sans ligne');
});

iron_test('Les valeurs sont re-nettoyées à la LECTURE', function () {

    $page  = iron_test_page();
    $field = iron_test_field('basic.title');

    // Écriture directe en base, en contournant le formulaire : c'est ce que
    // ferait un import, une migration ou WP-CLI.
    update_post_meta($page, $field['meta_key'], '<script>alert(1)</script>Bonjour');

    iron_assert_same('alert(1)Bonjour', iron_get_raw_value($field, $page), 'la valeur brute est déjà assainie');
    iron_assert_not_contains('<script', iron_field('basic.title', $page), 'rien de dangereux en sortie');

    $lien = iron_test_field('basic.cta');
    update_post_meta($page, $lien['meta_key'], ['url' => 'javascript:alert(9)', 'label' => 'Piege', 'target' => '_blank']);

    iron_assert_same('', iron_link('basic.cta', [], $page), 'aucun lien produit pour une URL hostile');

    delete_post_meta($page, $field['meta_key']);
    delete_post_meta($page, $lien['meta_key']);
});
