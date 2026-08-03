<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">

<head>

  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <?php
  // La balise <title> et les CSS sont produites par wp_head() :
  // `add_theme_support('title-tag')` dans inc/theme-setup.php pour le titre,
  // `wp_enqueue_style()` dans inc/assets.php pour les styles.
  wp_head();
  ?>

</head>

<body <?php body_class(); ?>>
