<?php
/**
 * Brique 5 — Épuration de l'interface d'administration pour le client.
 *
 * Tout ce fichier relève du confort et de la prévention du mauvais clic. Il ne
 * protège rien par lui-même : ce qui protège, ce sont les capacités du rôle
 * (`role.php`) et les verrous à l'enregistrement (`restrictions.php`).
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

if (!function_exists('iron_client_allowed_menus')) {
    /**
     * Les seules entrées de menu laissées au client.
     *
     * Volontairement une liste blanche et non une liste noire : le client
     * installera des plugins qu'on ne connaît pas, et chacun ajoutera ses
     * propres entrées. Une liste noire serait périmée dès le premier plugin.
     *
     * @return string[]
     */
    function iron_client_allowed_menus()
    {
        return apply_filters('iron_client_allowed_menus', [
            'index.php',                // Tableau de bord
            'edit.php?post_type=page',  // Pages
            IRON_OPTIONS_PAGE,          // Réglages du site (Brique 6)
            'upload.php',               // Médias
            'profile.php',              // Son propre compte
        ]);
    }
}

if (!function_exists('iron_client_clean_menu')) {
    /**
     * Retire tout ce qui n'est pas explicitement autorisé.
     *
     * @return void
     */
    function iron_client_clean_menu()
    {
        if (!iron_user_is_client()) {
            return;
        }

        global $menu, $submenu, $_wp_menu_nopriv, $_wp_submenu_nopriv;

        if (!is_array($menu)) {
            return;
        }

        $allowed = iron_client_allowed_menus();

        foreach ($menu as $item) {
            if (!isset($item[2])) {
                continue;
            }

            if (in_array($item[2], $allowed, true)) {
                continue;
            }

            // Retire aussi les séparateurs, qui laisseraient des trous.
            remove_menu_page($item[2]);

            // `remove_menu_page()` ne touche pas au sous-menu : sans ça, il
            // resterait orphelin dans `$submenu`.
            if (is_array($submenu) && isset($submenu[$item[2]])) {
                unset($submenu[$item[2]]);
            }
        }
    }
}
add_action('admin_menu', 'iron_client_clean_menu', 999);

if (!function_exists('iron_client_unlock_pages_screen')) {
    /**
     * Rend la liste des pages accessible au client.
     *
     * Sans ceci, le client voit le menu « Pages » mais reçoit une erreur 403
     * en cliquant dessus. Le comportement vient de WordPress, pas d'Ironframe :
     * il se produit pour n'importe quel rôle capable de modifier les pages
     * sans pouvoir modifier les articles.
     *
     * Le mécanisme, dans `user_can_access_admin_page()` :
     *
     *   foreach ( array_keys( $_wp_submenu_nopriv ) as $key ) {
     *       if ( isset( $_wp_submenu_nopriv[ $key ][ $pagenow ] ) ) return false;
     *   }
     *
     * WordPress cherche le fichier demandé parmi TOUS les écrans refusés, sans
     * vérifier qu'il s'agit du bon parent. Or la liste des articles et la liste
     * des pages sont **le même fichier**, `edit.php` : ne pas avoir le droit de
     * modifier les articles fait refuser les deux.
     *
     * On retire donc ce seul refus, et rien d'autre. La liste des articles
     * reste inaccessible — `edit.php` vérifie lui-même `edit_posts` et répond
     * 403. On ne touche ni aux autres refus, ni à `$_wp_menu_nopriv`, qui
     * protège les écrans de premier niveau.
     *
     * @return void
     */
    function iron_client_unlock_pages_screen()
    {
        if (!iron_user_is_client()) {
            return;
        }

        global $_wp_submenu_nopriv;

        if (isset($_wp_submenu_nopriv['edit.php']['edit.php'])) {
            unset($_wp_submenu_nopriv['edit.php']['edit.php']);
        }
    }
}
add_action('admin_menu', 'iron_client_unlock_pages_screen', 999);

if (!function_exists('iron_client_clean_admin_bar')) {
    /**
     * Allège la barre d'administration.
     *
     * @return void
     */
    function iron_client_clean_admin_bar()
    {
        if (!iron_user_is_client()) {
            return;
        }

        global $wp_admin_bar;

        foreach ([
            'wp-logo',
            'about',
            'comments',
            'new-content',
            'customize',
            'themes',
            'updates',
            'search',
        ] as $node) {
            $wp_admin_bar->remove_node($node);
        }
    }
}
add_action('wp_before_admin_bar_render', 'iron_client_clean_admin_bar');

if (!function_exists('iron_client_clean_page_screen')) {
    /**
     * Retire de l'écran d'édition tout ce que le client n'a pas à décider.
     *
     * La boîte « Attributs de page » part la première : elle contient le
     * sélecteur de template, dont dépend la liste des champs disponibles.
     *
     * @return void
     */
    function iron_client_clean_page_screen()
    {
        if (!iron_user_is_client()) {
            return;
        }

        $boxes = apply_filters('iron_client_removed_meta_boxes', [
            'pageparentdiv'     => 'side',  // Template, parent, ordre
            'postimagediv'      => 'side',  // Image mise en avant
            'submitdiv'         => 'side',  // Remplacée par notre propre boîte
            'slugdiv'           => 'normal',
            'authordiv'         => 'normal',
            'postcustom'        => 'normal',
            'postexcerpt'       => 'normal',
            'commentstatusdiv'  => 'normal',
            'commentsdiv'       => 'normal',
            'trackbacksdiv'     => 'normal',
            'revisionsdiv'      => 'normal',
        ]);

        foreach ($boxes as $box_id => $context) {
            remove_meta_box($box_id, 'page', $context);
        }

        // `submitdiv` porte le bouton « Mettre à jour » : on ne peut pas se
        // contenter de la retirer, il faut la remplacer par une boîte qui ne
        // contient que ce bouton.
        add_meta_box(
            'iron_publish',
            __('Enregistrer', 'ironframe'),
            'iron_client_render_publish_box',
            'page',
            'side',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'iron_client_clean_page_screen', 999);

if (!function_exists('iron_client_render_publish_box')) {
    /**
     * Boîte d'enregistrement réduite à l'essentiel.
     *
     * La boîte native expose le statut, la visibilité, la date de publication
     * et la mise à la corbeille. Aucun de ces réglages n'est du ressort du
     * client, et trois d'entre eux peuvent faire disparaître la page du site.
     *
     * @return void
     */
    function iron_client_render_publish_box()
    {
        ?>
        <div class="iron-publish">
            <p class="iron-publish__hint description">
                <?php esc_html_e('Vos modifications ne seront visibles sur le site qu\'après enregistrement.', 'ironframe'); ?>
            </p>
            <?php submit_button(__('Enregistrer les modifications', 'ironframe'), 'primary large', 'save', false); ?>
        </div>
        <?php
    }
}

if (!function_exists('iron_client_clean_dashboard')) {
    /**
     * Vide le tableau de bord et le remplace par la liste des pages.
     *
     * Le tableau de bord natif parle de WordPress ; le client, lui, cherche
     * ses pages.
     *
     * @return void
     */
    function iron_client_clean_dashboard()
    {
        if (!iron_user_is_client()) {
            return;
        }

        global $wp_meta_boxes;

        $wp_meta_boxes['dashboard'] = [];

        remove_action('welcome_panel', 'wp_welcome_panel');

        add_meta_box(
            'iron_dashboard_pages',
            __('Les pages du site', 'ironframe'),
            'iron_client_render_dashboard',
            'dashboard',
            'normal',
            'high'
        );
    }
}
add_action('wp_dashboard_setup', 'iron_client_clean_dashboard');

if (!function_exists('iron_client_render_dashboard')) {
    /**
     * @return void
     */
    function iron_client_render_dashboard()
    {
        $pages = get_pages([
            'sort_column' => 'menu_order,post_title',
            'post_status' => ['publish', 'draft'],
            'number'      => 50,
        ]);

        if (!$pages) {
            echo '<p>' . esc_html__('Aucune page pour le moment.', 'ironframe') . '</p>';

            return;
        }

        echo '<ul class="iron-dashboard-pages">';

        foreach ($pages as $page) {
            printf(
                '<li><a href="%s">%s</a> <a class="iron-dashboard-pages__view" href="%s" target="_blank" rel="noopener">%s</a></li>',
                esc_url(get_edit_post_link($page->ID)),
                esc_html(get_the_title($page)),
                esc_url(get_permalink($page)),
                esc_html__('voir', 'ironframe')
            );
        }

        echo '</ul>';
    }
}

if (!function_exists('iron_client_hide_screen_options')) {
    /**
     * Les Options de l'écran permettent de masquer des meta boxes — donc de
     * faire disparaître les champs sans comprendre pourquoi.
     *
     * @param bool $show
     * @return bool
     */
    function iron_client_hide_screen_options($show)
    {
        return iron_user_is_client() ? false : $show;
    }
}
add_filter('screen_options_show_screen', 'iron_client_hide_screen_options');

if (!function_exists('iron_client_remove_help_tabs')) {
    /**
     * @param WP_Screen $screen
     * @return void
     */
    function iron_client_remove_help_tabs($screen)
    {
        if (iron_user_is_client() && $screen instanceof WP_Screen) {
            $screen->remove_help_tabs();
        }
    }
}
add_action('current_screen', 'iron_client_remove_help_tabs');

if (!function_exists('iron_client_enqueue_admin_css')) {
    /**
     * Quelques masquages que seule la CSS permet d'obtenir proprement.
     *
     * @return void
     */
    function iron_client_enqueue_admin_css()
    {
        if (!iron_user_is_client()) {
            return;
        }

        wp_enqueue_style(
            'iron-client',
            IRON_URI . '/assets/admin/client.css',
            [],
            iron_asset_version('assets/admin/client.css')
        );
    }
}
add_action('admin_enqueue_scripts', 'iron_client_enqueue_admin_css');
