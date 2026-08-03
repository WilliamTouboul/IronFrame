<?php

/**
 * Template Name: Accueil
 *
 * Template de page natif. Son schéma de champs est dans `home.fields.php`.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_header();
?>

<section class="hero">

    <?php if (iron_has('hero.image')) : ?>
        <?= iron_image('hero.image', '16_9', ['class' => 'hero__bg']) ?>
    <?php endif; ?>

    <?php
    echo do_shortcode('[contact-form-7 id="e2971f5" title="Contact form 1"]');
    ?>

    <h1 class="hero__title"><?= iron_field('hero.title') ?></h1>

    <?php if (iron_has('hero.text')) : ?>
        <p class="hero__text"><?= iron_field('hero.text') ?></p>
    <?php endif; ?>

    <?= iron_link('hero.cta', ['class' => 'btn btn--primary']) ?>

</section>

<section class="services">
    <h2 class="services__title"><?= iron_field('services.title') ?></h2>
</section>

<?php
iron_footer();
