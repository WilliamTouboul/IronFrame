<?php
/**
 * Template Name: Accueil
 *
 * Gabarit de départ, volontairement minimal. Son schéma est dans
 * `accueil.fields.php`.
 *
 * Les classes CSS suivent une convention BEM et ne sont associées à aucun
 * style : la mise en forme est à écrire dans `assets/style/main.css`.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_header();
?>

<section class="hero">

    <?php if (iron_has('hero.image')) : ?>
        <?= iron_image('hero.image', '16_9', ['class' => 'hero__image']) ?>
    <?php endif; ?>

    <h1 class="hero__title"><?= iron_field('hero.title') ?></h1>

    <?php if (iron_has('hero.text')) : ?>
        <p class="hero__text"><?= iron_field('hero.text') ?></p>
    <?php endif; ?>

</section>

<?php
iron_footer();
