<?php
/**
 * API de lecture côté template.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_test('Les valeurs sont lues et échappées', function () {

    $page = iron_test_page();

    iron_save_raw_value(iron_test_field('basic.title'), 'Bonjour <b>vous</b>', $page);
    iron_save_raw_value(iron_test_field('basic.text'), "Un\nDeux", $page);
    iron_save_raw_value(iron_test_field('basic.align'), 'right', $page);

    iron_assert_same('Bonjour vous', iron_field('basic.title', $page), 'texte');
    iron_assert_same("Un<br />\nDeux", iron_field('basic.text', $page), 'texte long');
    iron_assert_same('right', iron_field('basic.align', $page), 'la liste de choix renvoie la clé');

    iron_assert_true(iron_has('basic.title', $page), 'champ rempli');
    iron_assert_false(iron_has('basic.cta', $page), 'champ vide');
});

iron_test('Le défaut ne s\'applique que tant que rien n\'est enregistré', function () {

    $page  = iron_test_page();
    $field = iron_test_field('basic.align');

    delete_post_meta($page, $field['meta_key']);
    iron_assert_same('left', iron_field('basic.align', $page), 'défaut appliqué avant tout enregistrement');

    iron_save_raw_value($field, '', $page);
    iron_assert_same('', iron_field('basic.align', $page), 'un champ vidé reste vide');

    iron_save_raw_value($field, 'right', $page);
});

iron_test('Images et liens produisent un balisage complet ou rien', function () {

    $page = iron_test_page();

    iron_save_raw_value(iron_test_field('basic.cta'), [
        'url'    => 'https://exemple.fr/page?a=1&b=2',
        'label'  => 'Voir',
        'target' => '_blank',
    ], $page);

    $lien = iron_link('basic.cta', ['class' => 'btn'], $page);

    iron_assert_contains('<a ', $lien, 'balise produite');
    iron_assert_contains('class="btn"', $lien, 'attribut transmis');
    iron_assert_contains('rel="noopener noreferrer"', $lien, 'noopener ajouté pour une cible _blank');
    iron_assert_same(1, substr_count($lien, '#038;'), 'l\'esperluette n\'est pas doublement encodée');

    iron_save_raw_value(iron_test_field('basic.cta'), ['url' => '', 'label' => '', 'target' => '_self'], $page);
    iron_assert_same('', iron_link('basic.cta', [], $page), 'aucune balise pour un lien vide');

    iron_save_raw_value(iron_test_field('basic.image'), 0, $page);
    iron_assert_same('', iron_image('basic.image', 'full', [], $page), 'aucune balise pour une image absente');
    iron_assert_same('', iron_image_url('basic.image', 'full', $page), 'aucune adresse pour une image absente');
});

iron_test('Une erreur de développeur renvoie du vide, jamais une fatale', function () {

    $page = iron_test_page();

    iron_assert_same('', iron_field('nawak.truc', $page), 'chemin inconnu');
    iron_assert_false(iron_has('nawak.truc', $page), 'iron_has sur un chemin inconnu');
    iron_assert_same('', iron_image('basic.title', 'full', [], $page), 'image demandée sur un texte');
    iron_assert_same('', iron_link('basic.title', [], $page), 'lien demandé sur un texte');
    iron_assert_same('', iron_field('listing.rows', $page), 'iron_field sur un répétable');
    iron_assert_same([], iron_rows('basic.title', $page), 'iron_rows sur un champ simple');
});

iron_test('La valeur brute reste accessible par un geste explicite', function () {

    $page = iron_test_page();

    iron_save_raw_value(iron_test_field('basic.title'), 'A & B', $page);

    iron_assert_same('A &amp; B', iron_field('basic.title', $page), 'échappée par défaut');
    iron_assert_same('A & B', iron_field_raw('basic.title', $page), 'brute sur demande');
});
