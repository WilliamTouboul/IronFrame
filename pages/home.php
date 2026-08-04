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

<?php
// Section désactivable : quand le client décoche la case dans l'administration,
// iron_has() renvoie faux et le bandeau disparaît. Aucun test supplémentaire à
// écrire ici.
?>
<?php if (iron_has('promo.title')) : ?>
    <aside class="promo">
        <p class="promo__message"><?= iron_field('promo.title') ?></p>
        <?= iron_link('promo.cta', ['class' => 'promo__link']) ?>
    </aside>
<?php endif; ?>

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

    <?php $services = iron_rows('services.items'); ?>

    <?php if ($services) : ?>
        <ul class="services__list">
            <?php foreach ($services as $service) : ?>
                <li class="service">

                    <?= iron_row_image($service, 'image', '400x300', ['class' => 'service__image']) ?>

                    <h3 class="service__title"><?= $service['title'] ?></h3>

                    <?php if (iron_row_has($service, 'text')) : ?>
                        <p class="service__text"><?= $service['text'] ?></p>
                    <?php endif; ?>

                    <?= iron_row_link($service, 'cta', ['class' => 'service__link']) ?>

                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</section>

<?php
iron_footer();
