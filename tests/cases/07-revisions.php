<?php
/**
 * Révisions des valeurs de champs, et outils de diagnostic.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_test('Les clés de champs sont déclarées comme révisionnables', function () {

    $keys = wp_post_revision_meta_keys('page');

    iron_assert_true(in_array(iron_test_field('basic.title')['meta_key'], $keys, true), 'un champ de page');
    iron_assert_true(in_array(iron_test_field('listing.rows')['meta_key'], $keys, true), 'une liste répétable');
    iron_assert_true(in_array(iron_group_toggle_key('toggled'), $keys, true), 'un interrupteur de section');

    iron_assert_same(
        [],
        array_intersect(iron_revisioned_meta_keys(), ['post_title', 'post_content']),
        'aucune clé parasite'
    );
});

iron_test('Une modification de champ seule crée bien une révision', function () {

    $page = iron_test_page();

    // L'éditeur étant retiré des pages, `post_content` ne change jamais : sans
    // le mécanisme de détection sur les metas, aucune révision ne serait créée.
    iron_test_submit($page, ['basic' => ['title' => 'Version un']]);
    $avant = count(wp_get_post_revisions($page));

    iron_test_submit($page, ['basic' => ['title' => 'Version deux']]);
    $apres = count(wp_get_post_revisions($page));

    iron_assert_true($apres > $avant, 'une révision a été créée');
});

iron_test('Restaurer une révision restaure les valeurs de champs', function () {

    $page  = iron_test_page();
    $liste = iron_test_field('listing.rows');

    // État initial, riche : un titre et trois lignes.
    iron_test_submit($page, [
        'basic'   => ['title' => 'Titre d\'origine'],
        'listing' => ['rows' => [
            ['name' => 'Alpha'],
            ['name' => 'Bravo'],
            ['name' => 'Charlie'],
        ]],
    ]);

    iron_assert_same(3, count(iron_rows('listing.rows', $page)), 'trois lignes enregistrées');

    $revisions = wp_get_post_revisions($page);
    $reference = array_shift($revisions);

    // Le client fait une bêtise : il vide la liste et change le titre.
    iron_test_submit($page, [
        'basic'   => ['title' => 'Titre écrasé'],
        'listing' => ['rows' => [['name' => 'Seule ligne restante']]],
    ]);

    iron_assert_same(1, count(iron_rows('listing.rows', $page)), 'les lignes ont bien été perdues');
    // On lit la valeur brute : ce test porte sur la restauration des données,
    // pas sur leur échappement — que couvre le cas 03.
    iron_assert_same('Titre écrasé', iron_field_raw('basic.title', $page), 'titre écrasé');

    // Le développeur restaure.
    wp_restore_post_revision($reference->ID);

    iron_assert_same(3, count(iron_rows('listing.rows', $page)), 'les trois lignes sont revenues');
    iron_assert_same('Alpha', iron_rows('listing.rows', $page)[0]['name'], 'contenu de la première ligne');
    iron_assert_same('Titre d\'origine', iron_field_raw('basic.title', $page), 'titre restauré');
});

iron_test('L\'état d\'un interrupteur de section est révisionné', function () {

    $page = iron_test_page();

    iron_test_submit($page, ['toggled' => ['note' => 'Bandeau']], ['toggled'], ['toggled']);
    iron_assert_true(iron_is_enabled('toggled', $page), 'section active');

    $revisions = wp_get_post_revisions($page);
    $reference = array_shift($revisions);

    iron_test_submit($page, ['toggled' => ['note' => 'Bandeau']], ['toggled'], []);
    iron_assert_false(iron_is_enabled('toggled', $page), 'section masquée');

    wp_restore_post_revision($reference->ID);
    iron_assert_true(iron_is_enabled('toggled', $page), 'section réactivée par la restauration');
});

iron_test('Le résumé de valeur est lisible pour tous les types', function () {

    iron_assert_same('(vide)', iron_value_summary('', iron_test_field('basic.title')), 'texte vide');
    iron_assert_same('Bonjour', iron_value_summary('Bonjour', iron_test_field('basic.title')), 'texte court');
    iron_assert_same('(vide)', iron_value_summary(0, iron_test_field('basic.image')), 'image absente');
    iron_assert_same('média #42', iron_value_summary(42, iron_test_field('basic.image')), 'image présente');
    iron_assert_same('2 lignes', iron_value_summary([1, 2], iron_test_field('listing.rows')), 'liste répétable');
    iron_assert_same(
        'Voir → https://exemple.fr',
        iron_value_summary(['url' => 'https://exemple.fr', 'label' => 'Voir'], iron_test_field('basic.cta')),
        'lien avec libellé'
    );

    $long = str_repeat('mot ', 40);

    iron_assert_true(mb_strlen(iron_value_summary($long, iron_test_field('basic.title'))) <= 48, 'texte long tronqué');
});

iron_test('La colonne Template s\'ajoute pour le développeur', function () {

    require_once IRON_PATH . '/inc/fields/admin/columns.php';

    $colonnes = iron_add_template_column(['title' => 'Titre', 'author' => 'Auteur', 'date' => 'Date']);

    iron_assert_true(isset($colonnes['iron_template']), 'colonne ajoutée');
    iron_assert_same('title,author,iron_template,date', implode(',', array_keys($colonnes)), 'insérée avant la date');

    ob_start();
    iron_render_template_column('iron_template', iron_test_page());
    $rendu = ob_get_clean();

    iron_assert_contains('gabarit de test', $rendu, 'nom du template affiché');
    iron_assert_contains('champs', $rendu, 'nombre de champs affiché');

    // Une page sans template dédié ne doit pas laisser une cellule vide.
    $sans_template = wp_insert_post([
        'post_type'   => 'page',
        'post_title'  => 'Ironframe — page sans template',
        'post_status' => 'draft',
    ]);

    $GLOBALS['iron_test_pages'][] = $sans_template;

    ob_start();
    iron_render_template_column('iron_template', $sans_template);
    $vide = ob_get_clean();

    iron_assert_contains('—', $vide, 'tiret pour une page sans template');
});
