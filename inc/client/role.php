<?php
/**
 * Brique 5 — Le rôle « Client ».
 *
 * Un rôle taillé pour une seule chose : remplir les champs déclarés par le
 * développeur et remplacer des images. Rien d'autre.
 *
 * Les capacités sont la vraie frontière de sécurité. Tout ce qui est masqué
 * dans `admin-ui.php` n'est que du confort : ça évite les mauvais clics, ça
 * n'empêche personne d'atteindre une URL.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

/** Identifiant du rôle client. */
define('IRON_ROLE_CLIENT', 'iron_client');

/**
 * Capacité de création de pages.
 *
 * Elle remplace `edit_pages` comme capacité `create_posts` du type `page`
 * (voir `restrictions.php`). Sans cette bascule, on ne pourrait pas retirer le
 * droit de créer une page sans retirer aussi celui de la modifier.
 */
define('IRON_CAP_CREATE_PAGES', 'iron_create_pages');

/**
 * Capacité d'accès aux options globales (Brique 6).
 *
 * Une capacité dédiée plutôt que `manage_options` : le client doit pouvoir
 * changer le téléphone du site sans obtenir au passage l'accès aux réglages
 * de WordPress.
 */
define('IRON_CAP_EDIT_OPTIONS', 'iron_edit_options');

/**
 * À incrémenter à chaque modification de la liste des capacités, pour que les
 * installations existantes soient mises à jour.
 */
define('IRON_ROLES_VERSION', '2');

if (!function_exists('iron_client_capabilities')) {
    /**
     * Capacités du rôle client.
     *
     * @return array<string, bool>
     */
    function iron_client_capabilities()
    {
        $caps = [
            // Accès à l'admin.
            'read' => true,

            // Remplacer une image passe par la médiathèque.
            'upload_files' => true,

            // Modifier les pages existantes, y compris celles créées par un
            // autre compte — sur un site livré, elles appartiennent à l'agence.
            'edit_pages'           => true,
            'edit_others_pages'    => true,
            'edit_published_pages' => true,

            // Contre-intuitif mais nécessaire : sans `publish_pages`,
            // WordPress rétrograde une page publiée en « en attente de
            // relecture » à chaque enregistrement du client. Le droit de créer
            // est retiré ailleurs, par la capacité dédiée ci-dessus.
            'publish_pages' => true,

            // Les données transversales du site : coordonnées, réseaux
            // sociaux, pied de page.
            IRON_CAP_EDIT_OPTIONS => true,
        ];

        /**
         * Permet d'ajuster les droits du client projet par projet.
         *
         * @param array<string, bool> $caps
         */
        return apply_filters('iron_client_capabilities', $caps);
    }
}

if (!function_exists('iron_install_roles')) {
    /**
     * (Re)crée le rôle client et distribue la capacité de création de pages.
     *
     * @return void
     */
    function iron_install_roles()
    {
        // On repart d'un rôle propre : c'est le seul moyen fiable de refléter
        // une capacité retirée entre deux versions.
        remove_role(IRON_ROLE_CLIENT);

        add_role(
            IRON_ROLE_CLIENT,
            __('Client', 'ironframe'),
            iron_client_capabilities()
        );

        // Sans cette redistribution, plus personne ne pourrait créer de page.
        foreach (['administrator', 'editor'] as $role_name) {
            $role = get_role($role_name);

            if ($role) {
                $role->add_cap(IRON_CAP_CREATE_PAGES);
                $role->add_cap(IRON_CAP_EDIT_OPTIONS);
            }
        }

        update_option('iron_roles_version', IRON_ROLES_VERSION);
    }
}

if (!function_exists('iron_maybe_install_roles')) {
    /**
     * Installe les rôles si nécessaire.
     *
     * Deux déclencheurs : un changement de version, et un filet de sécurité si
     * l'administrateur a perdu la capacité de créer des pages — sans quoi une
     * installation à moitié faite bloquerait tout le monde.
     *
     * @return void
     */
    function iron_maybe_install_roles()
    {
        if (get_option('iron_roles_version') !== IRON_ROLES_VERSION) {
            iron_install_roles();

            return;
        }

        $administrator = get_role('administrator');

        if ($administrator && !$administrator->has_cap(IRON_CAP_CREATE_PAGES)) {
            iron_install_roles();
        }
    }
}
add_action('init', 'iron_maybe_install_roles');
add_action('after_switch_theme', 'iron_install_roles');

if (!function_exists('iron_user_is_client')) {
    /**
     * L'utilisateur a-t-il le rôle client ?
     *
     * Un administrateur qui porterait aussi ce rôle n'est pas considéré comme
     * un client : on ne se verrouille pas soi-même hors de son propre site.
     *
     * @param int|WP_User|null $user
     * @return bool
     */
    function iron_user_is_client($user = null)
    {
        $user = $user instanceof WP_User ? $user : wp_get_current_user();

        if (!$user || !$user->exists()) {
            return false;
        }

        if (user_can($user, 'manage_options')) {
            return false;
        }

        return in_array(IRON_ROLE_CLIENT, (array) $user->roles, true);
    }
}
