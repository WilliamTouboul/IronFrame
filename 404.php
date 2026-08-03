<?php
/**
 * Page 404.
 *
 * À la racine du thème : c'est là que WordPress la cherche. L'ancien
 * `pages/404.php`, appelé par le routeur par slug, n'est plus utilisé.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_header();
?>

<section class="error-404">
    <h1><?php esc_html_e('Page introuvable', 'ironframe'); ?></h1>
    <p><?php esc_html_e('La page que vous cherchez n\'existe pas ou a été déplacée.', 'ironframe'); ?></p>
    <p><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Retour à l\'accueil', 'ironframe'); ?></a></p>
</section>

<?php
iron_footer();
