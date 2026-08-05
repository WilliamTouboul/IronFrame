<?php
/**
 * Template Name: Produits
 *
 * Gabarit intermédiaire : bandeau désactivable et catalogue en liste répétable.
 * Son schéma est dans `produit.fields.php`.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

iron_header();

$iron_produits = iron_rows('catalogue.items');
?>

<?php
// Section désactivable : quand le client décoche la case, iron_has() renvoie
// faux et le bandeau disparaît. Rien d'autre à tester ici.
?>
<?php if (iron_has('annonce.title')) : ?>
    <aside class="annonce">
        <p class="annonce__message"><?= iron_field('annonce.title') ?></p>
        <?= iron_link('annonce.cta', ['class' => 'annonce__lien']) ?>
    </aside>
<?php endif; ?>

<section class="intro">

    <h1 class="intro__title"><?= iron_field('intro.title') ?></h1>

    <?php if (iron_has('intro.text')) : ?>
        <p class="intro__text"><?= iron_field('intro.text') ?></p>
    <?php endif; ?>

</section>

<section class="catalogue">

    <?php if (iron_has('catalogue.title')) : ?>
        <h2 class="catalogue__title"><?= iron_field('catalogue.title') ?></h2>
    <?php endif; ?>

    <?php if ($iron_produits) : ?>
        <ul class="cartes">

            <?php foreach ($iron_produits as $iron_produit) : ?>

                <?php
                // La valeur d'une liste de choix est une clé du schéma, donc
                // une chaîne que le développeur a lui-même déclarée : elle est
                // sûre dans un nom de classe.
                $iron_classes = 'carte';

                if (iron_row_has($iron_produit, 'badge')) {
                    $iron_classes .= ' carte--' . iron_row($iron_produit, 'badge');
                }
                ?>

                <li class="<?= esc_attr($iron_classes) ?>">

                    <?= iron_row_image($iron_produit, 'image', '400x300', ['class' => 'carte__image']) ?>

                    <h3 class="carte__nom"><?= $iron_produit['name'] ?></h3>

                    <?php if (iron_row_has($iron_produit, 'price')) : ?>
                        <p class="carte__prix"><?= $iron_produit['price'] ?></p>
                    <?php endif; ?>

                    <?php if (iron_row_has($iron_produit, 'description')) : ?>
                        <p class="carte__description"><?= $iron_produit['description'] ?></p>
                    <?php endif; ?>

                    <?= iron_row_link($iron_produit, 'link', ['class' => 'carte__lien']) ?>

                </li>

            <?php endforeach; ?>

        </ul>
    <?php endif; ?>

</section>

<?php
iron_footer();
