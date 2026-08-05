<?php
/**
 * En-tête du site.
 *
 * Le logo et les entrées de menu se remplissent dans « Réglages du site ».
 * Voir `options/header.fields.php` pour la déclaration, et le commentaire qui
 * y explique pourquoi on n'utilise pas les menus natifs de WordPress.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

$iron_menu = iron_option_rows('header.items');
?>

<header class="site-header">

    <a class="site-header__brand" href="<?php echo esc_url(home_url('/')); ?>">
        <?php if (iron_option_has('header.logo')) : ?>
            <?= iron_option_image('header.logo', 'full', ['class' => 'site-header__logo']) ?>
        <?php else : ?>
            <?php echo esc_html(get_bloginfo('name')); ?>
        <?php endif; ?>
    </a>

    <?php if ($iron_menu) : ?>
        <nav class="site-nav" aria-label="<?php esc_attr_e('Navigation principale', 'ironframe'); ?>">
            <ul class="site-nav__list">
                <?php foreach ($iron_menu as $iron_entry) : ?>
                    <?php if (iron_row_has($iron_entry, 'link')) : ?>
                        <li class="site-nav__item">
                            <?= iron_row_link($iron_entry, 'link', ['class' => 'site-nav__link']) ?>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php endif; ?>

</header>
