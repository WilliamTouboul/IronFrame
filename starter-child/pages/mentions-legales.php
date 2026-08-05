<?php
/**
 * Template Name: Mentions légales
 *
 * Page réglementaire. Son schéma est dans `mentions-legales.fields.php`.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_header();

$iron_sections = iron_rows('contenu.sections');
?>

<article class="legal">

    <h1 class="legal__title"><?= iron_field('page.title') ?></h1>

    <?php if (iron_has('page.maj')) : ?>
        <p class="legal__maj">
            <?php
            printf(
                /* translators: %s : date de dernière mise à jour. */
                esc_html__('Dernière mise à jour : %s', 'ironframe'),
                iron_field('page.maj')
            );
            ?>
        </p>
    <?php endif; ?>

    <?php foreach ($iron_sections as $iron_section) : ?>
        <section class="legal__section">

            <?php if (iron_row_has($iron_section, 'title')) : ?>
                <h2 class="legal__section-title"><?= $iron_section['title'] ?></h2>
            <?php endif; ?>

            <?php if (iron_row_has($iron_section, 'text')) : ?>
                <p class="legal__section-text"><?= $iron_section['text'] ?></p>
            <?php endif; ?>

        </section>
    <?php endforeach; ?>

</article>

<?php
iron_footer();
