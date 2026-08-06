# Ironframe

**Thème WordPress blueprint à champs natifs.** Le développeur déclare en PHP les
champs éditables de chaque page ; le client final ne peut modifier que ceux-là,
et rien d'autre.

Le système de champs est entièrement natif — meta boxes, options et capacités
WordPress. Aucune dépendance à ACF ni à aucun plugin tiers pour la
fonctionnalité cœur. Le thème reste par ailleurs compatible avec les plugins que
le client voudra installer : SEO, multilingue, formulaires.

**Aucune étape de build.** Ni npm, ni Composer, ni Sass à compiler.

> Avant de juger ce qu'Ironframe ne fait pas, lisez [MANIFESTE.md](MANIFESTE.md).
> Chaque limite y est assumée, et la contrepartie annoncée.

---

## Prérequis

WordPress 6.0 ou plus récent, PHP 7.4 ou plus récent. Rien d'autre.

## Installation

1. Copiez ce dossier dans `wp-content/themes/`.
2. Activez le thème dans **Apparence → Thèmes**.
3. Le rôle **Client** est créé automatiquement.

Pendant le développement, activez `WP_DEBUG`. Ironframe signale les erreurs de
déclaration par des avertissements PHP qui n'apparaissent que dans ce mode.

---

## En deux minutes

**1. Le template** — `pages/accueil.php`

```php
<?php
/**
 * Template Name: Accueil
 */

defined('ABSPATH') || exit;

iron_header();
?>

<h1><?= iron_field('hero.title') ?></h1>
<?= iron_image('hero.image', '16_9') ?>

<?php
iron_footer();
```

**2. Le schéma** — `pages/accueil.fields.php`

```php
<?php

defined('ABSPATH') || exit;

return [
    'hero' => [
        'label'  => 'Bannière',
        'fields' => [
            'title' => ['type' => 'text',  'label' => 'Titre'],
            'image' => ['type' => 'image', 'label' => 'Visuel'],
        ],
    ],
];
```

**3. C'est tout.** Créez une page, choisissez le template « Accueil », la meta
box « Bannière » apparaît avec ses deux champs.

Le rattachement se fait par le nom du fichier : `accueil.php` cherche
`accueil.fields.php`. Rien à enregistrer, rien à déclarer ailleurs. Ajouter une
page, c'est ajouter deux fichiers.

---

## Où trouver quoi

| Dossier | Contenu |
|---|---|
| `pages/` | Un template de page et son schéma de champs |
| `options/` | Les schémas des réglages globaux du site |
| `templates/` | Les partiels réutilisables (en-tête, pied de page) |
| `assets/` | Les CSS et JS, front et administration |
| `inc/` | Le moteur. Vous n'avez normalement pas à y toucher |
| `docs/ARCHITECTURE.md` | Les décisions du projet et leur justification |

Les cinq types de champs disponibles sont `text`, `textarea`, `image`, `link` et
`repeater`. Toutes les fonctions publiques commencent par `iron`.

---

## Documentation complète

Installation détaillée, déclaration des champs, référence de l'API, listes
répétables, réglages du site et rôle client :

**https://ironframe.fr**

Le présent fichier n'est qu'un panneau d'orientation. Le pourquoi des choix de
conception est dans [MANIFESTE.md](MANIFESTE.md).

---

## Licence

Thème propriétaire. © William Touboul — https://williamtouboul.com
