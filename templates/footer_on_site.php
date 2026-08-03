<?php
/**
 * Pied de page du site.
 *
 * Démonstration des options globales (Brique 6) : ces valeurs ne sont
 * rattachées à aucune page, elles se remplissent une fois dans
 * « Réglages du site » et s'affichent partout.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;
?>

<footer class="site-footer">

    <?php if (iron_option_has('contact.address') || iron_option_has('contact.phone') || iron_option_has('contact.email')) : ?>
        <address class="site-footer__contact">

            <?php if (iron_option_has('contact.address')) : ?>
                <p><?= iron_option('contact.address') ?></p>
            <?php endif; ?>

            <?php if (iron_option_has('contact.phone')) : ?>
                <p><?= iron_option('contact.phone') ?></p>
            <?php endif; ?>

            <?php if (iron_option_has('contact.email')) : ?>
                <p><?= iron_option('contact.email') ?></p>
            <?php endif; ?>

        </address>
    <?php endif; ?>

    <?php if (iron_option_has('social.facebook') || iron_option_has('social.instagram') || iron_option_has('social.linkedin')) : ?>
        <nav class="site-footer__social">
            <?= iron_option_link('social.facebook', ['class' => 'social-link']) ?>
            <?= iron_option_link('social.instagram', ['class' => 'social-link']) ?>
            <?= iron_option_link('social.linkedin', ['class' => 'social-link']) ?>
        </nav>
    <?php endif; ?>

    <?php if (iron_option_has('footer.text')) : ?>
        <p class="site-footer__text"><?= iron_option('footer.text') ?></p>
    <?php endif; ?>

    <?= iron_option_link('footer.legal', ['class' => 'site-footer__legal']) ?>

</footer>
