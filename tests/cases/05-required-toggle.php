<?php
/**
 * Champs obligatoires et sections désactivables.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

/**
 * Simule une soumission du formulaire d'édition d'une page.
 *
 * @param int   $page_id
 * @param array $values
 * @param array $shown Groupes dont l'interrupteur était affiché.
 * @param array $on    Groupes dont l'interrupteur était coché.
 * @return void
 */
function iron_test_submit($page_id, array $values, array $shown = [], array $on = [])
{
    $_POST = [
        'iron_fields_nonce' => wp_create_nonce('iron_save_fields_' . $page_id),
        'iron'              => $values,
    ];

    foreach ($shown as $group) {
        $_POST['iron_toggle_shown'][$group] = '1';
    }

    foreach ($on as $group) {
        $_POST['iron_toggle'][$group] = '1';
    }

    // On passe par wp_update_post() plutôt que d'appeler iron_save_fields()
    // directement : c'est le seul moyen de dérouler toute la chaîne du core,
    // dont la création des révisions, qui a lieu sur `wp_after_insert_post`,
    // tout à la fin du cycle d'enregistrement.
    wp_update_post(['ID' => $page_id]);

    $_POST = [];
}

iron_test('Un champ obligatoire vidé sur une page EN LIGNE est refusé', function () {

    $page = iron_test_page();

    iron_test_submit($page, ['basic' => ['title' => 'Titre initial', 'text' => 'Un texte']]);
    iron_assert_same('Titre initial', iron_field('basic.title', $page), 'premier enregistrement');
    iron_assert_same([], iron_take_errors($page), 'aucune erreur');

    iron_test_submit($page, ['basic' => ['title' => '', 'text' => 'Texte modifié']]);

    iron_assert_same('Titre initial', iron_field('basic.title', $page), 'ancienne valeur conservée');
    iron_assert_same('publish', get_post($page)->post_status, 'la page reste en ligne');
    iron_assert_same('Texte modifié', iron_field('basic.text', $page), 'le champ optionnel passe quand même');

    $erreurs = iron_take_errors($page);

    iron_assert_same(1, count($erreurs), 'une erreur signalée');
    iron_assert_true(isset($erreurs['basic.title']), 'l\'erreur nomme le champ');
});

iron_test('Un champ optionnel peut être vidé', function () {

    $page = iron_test_page();

    iron_test_submit($page, ['basic' => ['title' => 'Titre', 'text' => '']]);

    iron_assert_same('', iron_field('basic.text', $page), 'champ optionnel vidé');
    iron_assert_same([], iron_take_errors($page), 'aucune erreur');
});

iron_test('Un brouillon incomplet ne peut pas être publié', function () {

    $brouillon = iron_test_page('draft');

    $_POST = [
        'iron_fields_nonce' => wp_create_nonce('iron_save_fields_' . $brouillon),
        'iron'              => ['basic' => ['title' => '']],
    ];

    wp_update_post(['ID' => $brouillon, 'post_status' => 'publish']);

    iron_assert_same('draft', get_post($brouillon)->post_status, 'publication refusée');
    iron_assert_same(1, count(iron_take_errors($brouillon)), 'erreur signalée');

    $_POST['iron'] = ['basic' => ['title' => 'Un titre']];

    wp_update_post(['ID' => $brouillon, 'post_status' => 'publish']);

    iron_assert_same('publish', get_post($brouillon)->post_status, 'publication acceptée une fois rempli');

    $_POST = [];
});

iron_test('Une section désactivée disparaît du front sans perdre son contenu', function () {

    $page = iron_test_page();

    iron_test_submit($page, ['toggled' => ['note' => 'Promotion de Noël']], ['toggled'], ['toggled']);

    iron_assert_true(iron_is_enabled('toggled', $page), 'section active');
    iron_assert_same('Promotion de Noël', iron_field('toggled.note', $page), 'contenu visible');

    iron_test_submit($page, ['toggled' => ['note' => 'Promotion de Noël']], ['toggled'], []);

    iron_assert_false(iron_is_enabled('toggled', $page), 'section masquée');
    iron_assert_same('', iron_field('toggled.note', $page), 'contenu masqué en front');
    iron_assert_false(iron_has('toggled.note', $page), 'iron_has faux');
    iron_assert_same(
        'Promotion de Noël',
        get_post_meta($page, iron_test_field('toggled.note')['meta_key'], true),
        'contenu toujours présent en base'
    );

    iron_assert_same([], iron_take_errors($page), 'obligatoire non exigé dans une section masquée');

    iron_test_submit($page, ['toggled' => ['note' => 'Promotion de Noël']], ['toggled'], ['toggled']);
    iron_assert_same('Promotion de Noël', iron_field('toggled.note', $page), 'contenu retrouvé à la réactivation');
});

iron_test('Un groupe sans interrupteur est toujours actif', function () {

    iron_assert_true(iron_is_enabled('basic', iron_test_page()), 'groupe simple');
    iron_assert_true(iron_is_enabled('groupe_inexistant', iron_test_page()), 'groupe inconnu');
});

iron_test('L\'écran d\'édition rend l\'interrupteur et l\'astérisque', function () {

    $page   = iron_test_page();
    $schema = iron_get_post_schema($page);

    ob_start();
    iron_render_meta_box(get_post($page), ['args' => ['group' => $schema['toggled']]]);
    $html = ob_get_clean();

    iron_assert_contains('name="iron_toggle[toggled]"', $html, 'case à cocher');
    iron_assert_contains('name="iron_toggle_shown[toggled]"', $html, 'témoin caché');
    iron_assert_contains('iron-field__required', $html, 'astérisque sur le champ obligatoire');

    ob_start();
    iron_render_meta_box(get_post($page), ['args' => ['group' => $schema['basic']]]);
    $simple = ob_get_clean();

    iron_assert_not_contains('iron_toggle[basic]', $simple, 'pas d\'interrupteur sur un groupe simple');
    iron_assert_contains('name="iron[basic][align]"', $simple, 'liste de choix rendue');
    iron_assert_contains('value="right"', $simple, 'options de la liste rendues');
});
