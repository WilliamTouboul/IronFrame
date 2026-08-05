<?php
/**
 * Template Name: Contact
 *
 * Gabarit transversal : il affiche surtout des données qui n'appartiennent pas
 * à la page. Coordonnées et réseaux sociaux viennent de « Réglages du site »,
 * lus avec les fonctions `iron_option_*()`.
 *
 * Son schéma, dans `contact.fields.php`, ne déclare que ce qui est propre à
 * cette page : introduction, horaires, accès, plan.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_header();

/*
 * Un numéro affiché contient des espaces, un lien « tel: » n'en veut pas.
 * C'est l'un des rares cas légitimes de `iron_option_raw()` : on récupère la
 * valeur non échappée pour la retraiter, puis on l'échappe soi-même.
 */
$iron_tel_brut = preg_replace('/[^0-9+]/', '', (string) iron_option_raw('contact.phone'));
?>

<section class="contact-intro">

    <h1 class="contact-intro__title"><?= iron_field('intro.title') ?></h1>

    <?php if (iron_has('intro.text')) : ?>
        <p class="contact-intro__text"><?= iron_field('intro.text') ?></p>
    <?php endif; ?>

</section>

<section class="coordonnees">

    <h2 class="coordonnees__title"><?php esc_html_e('Nos coordonnées', 'ironframe'); ?></h2>

    <address class="coordonnees__bloc">

        <?php if (iron_option_has('contact.address')) : ?>
            <p class="coordonnees__adresse"><?= iron_option('contact.address') ?></p>
        <?php endif; ?>

        <?php if (iron_option_has('contact.phone')) : ?>
            <p class="coordonnees__tel">
                <a href="<?php echo esc_url('tel:' . $iron_tel_brut); ?>">
                    <?= iron_option('contact.phone') ?>
                </a>
            </p>
        <?php endif; ?>

        <?php if (iron_option_has('contact.email')) : ?>
            <p class="coordonnees__email">
                <a href="<?php echo esc_url('mailto:' . iron_option_raw('contact.email')); ?>">
                    <?= iron_option('contact.email') ?>
                </a>
            </p>
        <?php endif; ?>

    </address>

    <?php if (iron_option_has('social.facebook') || iron_option_has('social.instagram') || iron_option_has('social.linkedin')) : ?>
        <nav class="coordonnees__reseaux" aria-label="<?php esc_attr_e('Réseaux sociaux', 'ironframe'); ?>">
            <?= iron_option_link('social.facebook', ['class' => 'reseau']) ?>
            <?= iron_option_link('social.instagram', ['class' => 'reseau']) ?>
            <?= iron_option_link('social.linkedin', ['class' => 'reseau']) ?>
        </nav>
    <?php endif; ?>

</section>

<?php if (iron_has('pratique.horaires') || iron_has('pratique.acces') || iron_has('pratique.plan')) : ?>
    <section class="pratique">

        <h2 class="pratique__title"><?php esc_html_e('Informations pratiques', 'ironframe'); ?></h2>

        <?php if (iron_has('pratique.horaires')) : ?>
            <div class="pratique__horaires">
                <h3><?php esc_html_e('Horaires', 'ironframe'); ?></h3>
                <p><?= iron_field('pratique.horaires') ?></p>
            </div>
        <?php endif; ?>

        <?php if (iron_has('pratique.acces')) : ?>
            <div class="pratique__acces">
                <h3><?php esc_html_e('Accès', 'ironframe'); ?></h3>
                <p><?= iron_field('pratique.acces') ?></p>
            </div>
        <?php endif; ?>

        <?php if (iron_has('pratique.plan')) : ?>
            <?= iron_image('pratique.plan', '600x400', ['class' => 'pratique__plan']) ?>
        <?php endif; ?>

    </section>
<?php endif; ?>

<section class="formulaire">

    <h2 class="formulaire__title"><?php esc_html_e('Nous écrire', 'ironframe'); ?></h2>

    <?php
    /*
     * Ironframe ne fournit pas de formulaire : c'est le travail d'une
     * extension, qui gère l'anti-spam, les envois et la conformité RGPD mieux
     * qu'un thème ne le ferait.
     *
     * Collez ici le shortcode de l'extension retenue, par exemple :
     *
     *     echo do_shortcode('[contact-form-7 id="..." title="Contact"]');
     *
     * Pensez à ouvrir le menu de l'extension au rôle Client s'il doit pouvoir
     * changer l'adresse de réception, avec le filtre `iron_client_allowed_menus`.
     */
    ?>

</section>

<?php
iron_footer();
