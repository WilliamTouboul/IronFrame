<?php
/**
 * Template de repli.
 *
 * Depuis le passage aux templates de page natifs, ce fichier ne route plus
 * rien : WordPress appelle directement le fichier de `pages/` sélectionné sur
 * la page. index.php ne sert donc plus qu'aux contenus sans template dédié.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_header();

while (have_posts()) :
    the_post();
    ?>

    <article <?php post_class(); ?>>
        <h1><?php the_title(); ?></h1>
        <?php the_content(); ?>
    </article>

    <?php
endwhile;

iron_footer();
