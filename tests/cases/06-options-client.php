<?php
/**
 * Réglages globaux et rôle client.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_test('Les schémas d\'options livrés sont valides', function () {

    $schema = iron_get_options_schema();

    iron_assert_true(count($schema) > 0, 'au moins un groupe déclaré');

    foreach ($schema as $key => $group) {
        iron_assert_true(isset($group['fields']) && $group['fields'], sprintf('le groupe « %s » a des champs', $key));
    }

    iron_assert_true(isset($schema['header']['fields']['items']), 'le menu principal est déclaré');
    iron_assert_same('repeater', $schema['header']['fields']['items']['type'], 'le menu est une liste répétable');
});

iron_test('Une option s\'écrit et se relit avec les mêmes garanties', function () {

    // Définition synthétique : les tests ne dépendent pas des options livrées,
    // qui ont vocation à être adaptées projet par projet.
    $field = [
        'key'         => 'sujet',
        'group'       => 'iron_test',
        'path'        => 'iron_test.sujet',
        'type'        => 'text',
        'label'       => 'Sujet',
        'desc'        => '',
        'default'     => '',
        'required'    => false,
        'option_name' => 'iron_opt_iron_test_sujet',
    ];

    iron_save_raw_option($field, '<script>x</script>Bonjour');

    iron_assert_same('xBonjour', iron_get_raw_option($field), 'nettoyé à l\'écriture');
    iron_assert_same('', iron_get_raw_option(array_merge($field, ['option_name' => 'iron_opt_inexistante'])), 'option jamais enregistrée');

    delete_option($field['option_name']);
});

iron_test('L\'API des options se comporte comme celle des pages', function () {

    iron_assert_same('', iron_option('nawak.truc'), 'chemin inconnu');
    iron_assert_false(iron_option_has('nawak.truc'), 'iron_option_has sur un chemin inconnu');
    iron_assert_same([], iron_option_rows('nawak.truc'), 'iron_option_rows sur un chemin inconnu');
    iron_assert_true(iron_option_is_enabled('header'), 'groupe sans interrupteur toujours actif');
});

iron_test('Le rôle client existe avec les bons droits', function () {

    $role = get_role(IRON_ROLE_CLIENT);

    iron_assert_true($role instanceof WP_Role, 'le rôle existe');

    foreach (['read', 'upload_files', 'edit_pages', 'edit_others_pages', 'edit_published_pages', 'publish_pages', IRON_CAP_EDIT_OPTIONS] as $cap) {
        iron_assert_true($role->has_cap($cap), sprintf('accordé : %s', $cap));
    }

    foreach ([IRON_CAP_CREATE_PAGES, 'delete_pages', 'manage_options', 'switch_themes', 'install_plugins', 'list_users', 'edit_posts', 'edit_theme_options'] as $cap) {
        iron_assert_false($role->has_cap($cap), sprintf('refusé : %s', $cap));
    }
});

iron_test('Les administrateurs conservent le droit de créer des pages', function () {

    $admin = get_role('administrator');

    iron_assert_true($admin->has_cap(IRON_CAP_CREATE_PAGES), 'capacité de création');
    iron_assert_true($admin->has_cap(IRON_CAP_EDIT_OPTIONS), 'capacité sur les réglages');
    iron_assert_same(IRON_CAP_CREATE_PAGES, get_post_type_object('page')->cap->create_posts, 'capacité de création basculée sur le type page');
});

iron_test('Un client ne peut modifier ni le statut, ni le permalien, ni le template', function () {

    $page = iron_test_page();

    $client_id = wp_insert_user([
        'user_login' => 'iron_test_client_' . wp_generate_password(6, false),
        'user_pass'  => wp_generate_password(24),
        'user_email' => 'iron-test-' . wp_generate_password(6, false) . '@example.invalid',
        'role'       => IRON_ROLE_CLIENT,
    ]);

    if (is_wp_error($client_id)) {
        iron_assert_true(false, 'création du client de test : ' . $client_id->get_error_message());

        return;
    }

    $avant     = get_post($page);
    $precedent = get_current_user_id();

    wp_set_current_user($client_id);

    iron_assert_true(iron_user_is_client(), 'reconnu comme client');
    iron_assert_false(current_user_can(get_post_type_object('page')->cap->create_posts), 'ne peut pas créer de page');

    wp_update_post([
        'ID'          => $page,
        'post_status' => 'draft',
        'post_name'   => 'slug-detourne',
        'post_title'  => 'Titre modifié par le client',
    ]);

    update_post_meta($page, '_wp_page_template', 'pages/home.php');

    $apres = get_post($page);

    iron_assert_same($avant->post_status, $apres->post_status, 'statut inchangé');
    iron_assert_same($avant->post_name, $apres->post_name, 'permalien inchangé');
    iron_assert_same(iron_test_template(), get_page_template_slug($page), 'template inchangé');
    iron_assert_same('Titre modifié par le client', $apres->post_title, 'le titre reste modifiable');

    wp_set_current_user($precedent);
    wp_update_post(['ID' => $page, 'post_title' => $avant->post_title]);

    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($client_id);
});
