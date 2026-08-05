<?php
/**
 * Lanceur de tests d'Ironframe.
 *
 * Aucune dépendance : ni PHPUnit, ni Composer, ni étape de build. Le script
 * charge WordPress, déroule les cas de `tests/cases/`, et sort avec un code
 * d'erreur si quoi que ce soit échoue.
 *
 * Usage, avec le PHP de l'environnement local :
 *
 *   php -n -d extension_dir=<php>/ext \
 *       -d extension=php_mbstring.dll -d extension=php_mysqli.dll \
 *       -d extension=php_openssl.dll -d mysqli.default_port=<port> \
 *       tests/run.php
 *
 * Les tests créent leurs propres données et les suppriment à la fin. Ils ne
 * touchent à aucun contenu existant.
 *
 * @package Ironframe
 */

if ('cli' !== PHP_SAPI) {
    exit("Ce script ne s'exécute qu'en ligne de commande.\n");
}

define('WP_USE_THEMES', false);

/* -------------------------------------------------------------------------- */
/* Chargement de WordPress                                                    */
/* -------------------------------------------------------------------------- */

$iron_dir = __DIR__;

while (!file_exists($iron_dir . '/wp-load.php')) {
    $parent = dirname($iron_dir);

    if ($parent === $iron_dir) {
        exit("wp-load.php introuvable en remontant depuis " . __DIR__ . "\n");
    }

    $iron_dir = $parent;
}

require $iron_dir . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

// En CLI, `is_admin()` est faux : les modules d'administration ne sont donc pas
// chargés par functions.php. On les monte à la main pour pouvoir les tester.
foreach ([
    'inc/fields/admin/render.php',
    'inc/fields/admin/validation.php',
    'inc/fields/admin/meta-box.php',
    'inc/options/admin.php',
    'inc/fields/admin/columns.php',
    'inc/client/admin-ui.php',
] as $iron_admin_module) {
    require_once IRON_PATH . '/' . $iron_admin_module;
}

/* -------------------------------------------------------------------------- */
/* Assertions                                                                 */
/* -------------------------------------------------------------------------- */

$GLOBALS['iron_test_results'] = ['pass' => 0, 'fail' => 0, 'failures' => []];
$GLOBALS['iron_test_current'] = '';

if (!function_exists('iron_test')) {
    /**
     * Déclare et exécute un cas de test.
     *
     * @param string   $name
     * @param callable $body
     * @return void
     */
    function iron_test($name, callable $body)
    {
        $GLOBALS['iron_test_current'] = $name;

        printf("  %s\n", $name);

        try {
            $body();
        } catch (Throwable $e) {
            _iron_test_record(false, sprintf('exception : %s', $e->getMessage()));
        }
    }
}

if (!function_exists('_iron_test_record')) {
    /**
     * @param bool   $ok
     * @param string $label
     * @return void
     */
    function _iron_test_record($ok, $label)
    {
        if ($ok) {
            $GLOBALS['iron_test_results']['pass']++;

            printf("    OK    %s\n", $label);

            return;
        }

        $GLOBALS['iron_test_results']['fail']++;
        $GLOBALS['iron_test_results']['failures'][] = $GLOBALS['iron_test_current'] . ' → ' . $label;

        printf("    ECHEC %s\n", $label);
    }
}

if (!function_exists('_iron_test_export')) {
    /**
     * Représentation courte d'une valeur, pour les messages.
     *
     * @param mixed $value
     * @return string
     */
    function _iron_test_export($value)
    {
        $text = is_scalar($value) || null === $value
            ? var_export($value, true)
            : str_replace(["\n", '  '], '', var_export($value, true));

        return strlen($text) > 70 ? substr($text, 0, 67) . '...' : $text;
    }
}

if (!function_exists('iron_assert_same')) {
    /**
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $label
     * @return void
     */
    function iron_assert_same($expected, $actual, $label)
    {
        $ok = $expected === $actual;

        _iron_test_record($ok, $ok ? $label : sprintf(
            '%s — attendu %s, obtenu %s',
            $label,
            _iron_test_export($expected),
            _iron_test_export($actual)
        ));
    }
}

if (!function_exists('iron_assert_true')) {
    /**
     * @param mixed  $actual
     * @param string $label
     * @return void
     */
    function iron_assert_true($actual, $label)
    {
        iron_assert_same(true, (bool) $actual, $label);
    }
}

if (!function_exists('iron_assert_false')) {
    /**
     * @param mixed  $actual
     * @param string $label
     * @return void
     */
    function iron_assert_false($actual, $label)
    {
        iron_assert_same(false, (bool) $actual, $label);
    }
}

if (!function_exists('iron_assert_contains')) {
    /**
     * @param string $needle
     * @param string $haystack
     * @param string $label
     * @return void
     */
    function iron_assert_contains($needle, $haystack, $label)
    {
        $ok = is_string($haystack) && false !== strpos($haystack, $needle);

        _iron_test_record($ok, $ok ? $label : sprintf('%s — « %s » absent', $label, $needle));
    }
}

if (!function_exists('iron_assert_not_contains')) {
    /**
     * @param string $needle
     * @param string $haystack
     * @param string $label
     * @return void
     */
    function iron_assert_not_contains($needle, $haystack, $label)
    {
        $ok = !is_string($haystack) || false === strpos($haystack, $needle);

        _iron_test_record($ok, $ok ? $label : sprintf('%s — « %s » présent alors qu\'il ne devrait pas', $label, $needle));
    }
}

/* -------------------------------------------------------------------------- */
/* Données de test                                                            */
/* -------------------------------------------------------------------------- */

if (!function_exists('iron_test_template')) {
    /**
     * Le template de test vit dans `tests/fixtures/`, à deux niveaux de
     * profondeur : WordPress ne le référence donc pas comme template de page,
     * et il n'apparaît pas dans la liste proposée au client.
     *
     * Les tests ne dépendent ainsi pas de `pages/home.fields.php`, qui a
     * vocation à être remplacé projet par projet.
     *
     * @return string
     */
    function iron_test_template()
    {
        return 'tests/fixtures/test-page.php';
    }
}

/**
 * Rend le gabarit de test acceptable par WordPress, le temps de la suite.
 *
 * Sans cela, `wp_update_post()` compare `page_template` à la liste des
 * templates enregistrés, n'y trouve pas le nôtre — il est volontairement à
 * deux niveaux de profondeur pour rester invisible au client — et le remet
 * silencieusement à « default », ce qui vide le schéma de la page.
 *
 * Ce filtre vit dans la suite de tests, jamais dans le thème.
 */
add_filter('theme_page_templates', function ($templates) {

    $templates[iron_test_template()] = 'Ironframe — gabarit de test';

    return $templates;
});

/**
 * Déclare les champs du schéma de test comme révisionnables.
 *
 * Le thème ne parcourt que `pages/` ; le schéma de test vit ailleurs. On passe
 * donc par le filtre prévu, ce qui le vérifie au passage.
 *
 * Le schéma est résolu depuis le chemin du gabarit et non depuis une page :
 * créer une page ici déclencherait une révision, donc un appel à ce même
 * filtre, donc une récursion.
 */
add_filter('iron_revisioned_meta_keys', function ($keys) {

    foreach (iron_get_schema(iron_test_template()) as $group_key => $group) {

        if (!empty($group['toggle'])) {
            $keys[] = iron_group_toggle_key($group_key);
        }

        foreach ($group['fields'] as $field) {
            $keys[] = $field['meta_key'];
        }
    }

    return array_values(array_unique($keys));
});

if (!function_exists('iron_test_page')) {
    /**
     * Page de test, créée une fois et réutilisée.
     *
     * @param string $status
     * @return int
     */
    function iron_test_page($status = 'publish')
    {
        static $ids = [];

        if (isset($ids[$status])) {
            return $ids[$status];
        }

        $id = wp_insert_post([
            'post_type'   => 'page',
            'post_title'  => 'Ironframe — page de test (' . $status . ')',
            'post_status' => $status,
        ]);

        if (is_wp_error($id)) {
            exit('Création de la page de test impossible : ' . $id->get_error_message() . "\n");
        }

        // Écriture directe : le template de test n'est pas enregistré auprès de
        // WordPress, `wp_insert_post()` le rejetterait.
        update_post_meta($id, '_wp_page_template', iron_test_template());

        $ids[$status] = $id;
        $GLOBALS['iron_test_pages'][] = $id;

        return $id;
    }
}

if (!function_exists('iron_test_attachment')) {
    /**
     * Média factice : suffisant pour les contrôles de type, qui vérifient
     * qu'un identifiant pointe bien vers une pièce jointe existante.
     *
     * @return int
     */
    function iron_test_attachment()
    {
        static $id = null;

        if (null !== $id) {
            return $id;
        }

        $id = wp_insert_post([
            'post_type'      => 'attachment',
            'post_title'     => 'Ironframe — média de test',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image/jpeg',
        ]);

        $GLOBALS['iron_test_pages'][] = $id;

        return $id;
    }
}

if (!function_exists('iron_test_field')) {
    /**
     * Raccourci vers la définition d'un champ du schéma de test.
     *
     * @param string $path
     * @return array|null
     */
    function iron_test_field($path)
    {
        return iron_get_field_definition($path, iron_test_page());
    }
}

/* -------------------------------------------------------------------------- */
/* Exécution                                                                  */
/* -------------------------------------------------------------------------- */

$GLOBALS['iron_test_pages'] = [];

// Les tests s'exécutent en tant qu'administrateur : c'est le contexte dans
// lequel les écrans d'édition sont rendus.
$iron_admins = get_users(['role' => 'administrator', 'number' => 1]);

if ($iron_admins) {
    wp_set_current_user($iron_admins[0]->ID);
}

$iron_cases = glob(__DIR__ . '/cases/*.php');

sort($iron_cases);

printf("Ironframe — %d fichiers de cas\n\n", count($iron_cases));

foreach ($iron_cases as $iron_case) {
    printf("%s\n", basename($iron_case, '.php'));
    require $iron_case;
    echo "\n";
}

/* -------------------------------------------------------------------------- */
/* Nettoyage et bilan                                                         */
/* -------------------------------------------------------------------------- */

foreach (array_unique($GLOBALS['iron_test_pages']) as $iron_page_id) {
    wp_delete_post($iron_page_id, true);
}

$iron_results = $GLOBALS['iron_test_results'];

echo str_repeat('-', 60) . "\n";
printf("%d assertions — %d réussies, %d échouées\n", $iron_results['pass'] + $iron_results['fail'], $iron_results['pass'], $iron_results['fail']);

if ($iron_results['failures']) {
    echo "\nEchecs :\n";

    foreach ($iron_results['failures'] as $iron_failure) {
        printf("  - %s\n", $iron_failure);
    }
}

exit($iron_results['fail'] > 0 ? 1 : 0);
