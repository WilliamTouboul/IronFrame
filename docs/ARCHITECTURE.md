# Architecture — Thème WordPress générique à champs natifs

> Document de référence du projet. Décrit l'intention, les décisions et leur
> justification. À relire avant toute décision structurante.
> Le suivi opérationnel (conventions, état d'avancement) est dans le `CLAUDE.md`
> à la racine du projet.

---

## 1. Contexte et problème métier

Le thème actuel repose sur **ACF (Advanced Custom Fields)** pour permettre
l'édition de contenu par le client final via des champs personnalisés (titre,
texte, image) injectés directement dans le template.

Ce système pose deux problèmes récurrents :

- **Fragilité de dépendance.** ACF est un plugin tiers, sujet à des incidents de
  sécurité (cas vécu il y a 1-2 ans) et à des conflits avec d'autres plugins. Un
  incident sur ACF met en péril *tous* les sites clients simultanément.
- **Surface d'attaque inutile.** Chaque plugin ajouté est une alerte de sécurité
  potentielle et une dépendance à maintenir sur chaque site client.

## 2. Objectif du projet

Concevoir un thème WordPress **générique et réutilisable**, servant de blueprint
à personnaliser client par client, qui :

- Remplace ACF par un système de champs personnalisés natif (Custom Fields /
  Meta Boxes WordPress, sans plugin externe).
- Reste **extrêmement restrictif côté client final** : pas de Gutenberg complet,
  pas de liberté de mise en page.
- Reste **confortable côté développeur** pour déclarer rapidement les champs
  disponibles par page / template.
- Élimine la dépendance à un plugin tiers **pour la fonctionnalité cœur du
  thème**.

### Périmètre de l'indépendance — précision importante

L'objectif **n'est pas** de se passer de tout plugin. L'écosystème de plugins est
précisément l'intérêt de WordPress, et le thème doit rester compatible avec
l'installation de plugins tiers (SEO, multilingue, formulaires, sécurité,
cache…).

L'objectif est de **ne dépendre d'aucun plugin pour le système de champs**, qui
est le cœur de la valeur ajoutée du thème et le point de fragilité identifié.

Corollaire concret : on n'internalise pas le multilingue, ni les formulaires, ni
le SEO. On internalise uniquement ACF.

## 3. Principe de fonctionnement

1. Le développeur déclare, **dans le code du thème**, quels champs sont
   disponibles pour un template donné (ex. page d'accueil = titre + texte +
   image).
2. Ces champs apparaissent dans l'admin WordPress sous forme de **meta box
   simple**, directement sur l'écran d'édition de la page.
3. Le client remplit **uniquement** ces champs, sans accès à l'éditeur de blocs
   complet.
4. Le thème récupère ces valeurs via des **fonctions maison** (équivalent de
   `get_field()` côté ACF) et les injecte dans le HTML (`<h1>`, `<p>`, `src`
   d'image, etc.).

Avantage collatéral non prévu au départ mais réel : les champs étant déclarés en
PHP, ils sont **versionnés dans Git**. Plus de synchronisation JSON à gérer comme
avec ACF, plus de divergence entre environnements.

## 4. Découpage en briques

Les briques sont validées **une par une**. Aucun enchaînement automatique.

### Brique 1 — Système d'enregistrement des types de champs — **faite**

- Définir une structure de déclaration des champs (nom, type, template associé).
- Types retenus pour la V1 : **`text`, `textarea`, `image`, `link`** (cf. §6).
- Prévoir dès le départ l'extensibilité vers d'autres types (lien, couleur, champ
  répétable pour galerie) **sans** les implémenter en V1.

  *Position retenue :* le moteur doit reposer sur un registre de types
  extensible, où ajouter un type = ajouter un fichier, sans toucher au noyau. Le
  coût de cette extensibilité est faible si elle est prévue dès le départ, et
  très élevé si elle est ajoutée après coup. En revanche, les types eux-mêmes
  (lien, couleur, répéteur) attendent un besoin réel sur un projet client.

### Brique 2 — Interface d'édition dans l'admin — **faite**

- Génération **automatique** d'une meta box sur l'écran d'édition de page, à
  partir des champs déclarés en Brique 1.
- Interface volontairement minimaliste : aucune configuration côté client,
  uniquement remplissage.
- Éditeur de contenu natif restreint (pas de Gutenberg complet), cohérent avec
  l'existant (`remove_post_type_support('page', 'editor')` est déjà en place).

### Brique 3 — Fonctions de récupération côté template — **faite**

- Fonction maison équivalente à `get_field('nom_du_champ')`.
- Usage direct dans les fichiers de template.
- Doit rester **aussi simple à écrire** que l'appel ACF d'origine — si le
  template devient plus verbeux qu'avec ACF, la brique a raté sa cible.

Surface livrée, dans `inc/fields/api.php` : `iron_field()`, `iron_has()`,
`iron_image()`, `iron_image_url()`, `iron_link()`, `iron_field_raw()`.

```php
<h1><?= iron_field('hero.title') ?></h1>
<?= iron_image('hero.image', '16_9', ['class' => 'hero__bg']) ?>
<?= iron_link('hero.cta', ['class' => 'btn']) ?>
```

### Brique 4 — Sécurisation et échappement des données

- Échappement **systématique en sortie** (`esc_html`, `esc_url`, `esc_attr`
  selon le contexte).
- Validation / sanitization **en entrée** (`sanitize_text_field`, etc.).
- Point jugé prioritaire vu l'historique de faille avec ACF.

  *Position retenue :* l'échappement doit être le **comportement par défaut** de
  l'API de lecture, pas une option que le développeur pense à activer. Un champ
  rempli par un client ne doit jamais pouvoir injecter de HTML actif sans une
  intention explicite du développeur.

### Brique 5 — Verrouillage du client final — **faite**

Brique ajoutée en cours de projet : elle ne figurait pas dans le découpage
initial, alors qu'elle porte la moitié de la promesse produit. Un système de
champs restrictif ne sert à rien si le client peut par ailleurs changer de
thème, supprimer une page ou casser un permalien.

- Un rôle **Client** (`iron_client`) dédié : modifier les pages existantes,
  remplacer des images, rien d'autre.
- Création et suppression de pages retirées. Les templates étant des fichiers,
  une page créée par le client n'aurait de toute façon aucun rendu.
- Statut, permalien, page parente et template figés à l'enregistrement.
- Menus d'administration en **liste blanche**.

Séparation à ne jamais confondre : les **capacités** et les **verrous à
l'enregistrement** sont la frontière de sécurité ; le masquage de l'interface
n'est que de la prévention du mauvais clic.

### Brique 6 — Options globales — **faite**

L'équivalent de la page d'options d'ACF : les contenus qui n'appartiennent à
aucune page en particulier (coordonnées, réseaux sociaux, pied de page, mentions
légales). Même moteur de schéma que la Brique 1, même registre de types, même
rendu de champs — ne changent que le stockage (`wp_options` au lieu de
`wp_postmeta`) et le contenant (une page d'admin au lieu d'une meta box).

- Déclaration dans `options/*.fields.php`, découverte automatique, tous les
  fichiers fusionnés en un seul écran. Une section par groupe.
- Lecture par `iron_option()`, `iron_option_has()`, `iron_option_image()`,
  `iron_option_image_url()`, `iron_option_link()`, `iron_option_raw()`.
- Accès gouverné par une capacité dédiée, `iron_edit_options`, accordée au
  client — surtout pas `manage_options`.

## 5. Contraintes transverses

- **Aucune dépendance à un plugin tiers pour le système de champs.** C'est le
  cœur de la valeur ajoutée du projet.
- **Aucune toolchain de build** (npm, webpack, Composer) requise pour faire
  tourner le thème. Il doit s'installer par simple copie de dossier. C'est ce qui
  écarte l'approche « blocs Gutenberg custom », qui serait la solution officielle
  au problème mais imposerait une chaîne de build JS.
- **Thème pensé comme un blueprint générique** : la structure des champs par
  template doit être facile à dupliquer / adapter pour chaque nouveau client.
- **Compatibilité avec une vente en marque blanche** (thème payant). Implique un
  préfixe de fonctions renommable, pas de nom de marque en dur dans le code, et
  une documentation développeur.

## 6. Décisions structurantes

| Sujet | Décision | Justification |
|---|---|---|
| Moteur d'édition | Meta boxes classiques | Pas de toolchain JS. L'éditeur est déjà retiré des pages, l'écran classique s'affiche donc nativement. |
| Stockage | Hybride : une meta par champ scalaire (clé plate préfixée), un blob JSON pour les structures répétables | Reste lisible en base et interrogeable en `meta_query`, sans reproduire le schéma `field_0_sous_champ` d'ACF. |
| Échappement | Par défaut dans l'API de lecture | Voir Brique 4. |
| Périmètre plugins | Seul ACF est remplacé | Voir §2. |
| Rattachement des champs | Au **template de page natif** (`Template Name:`), pas au slug | Le routeur par slug tombe en 404 dès que le client renomme une page. Rattacher au template découple le contenu de l'URL et permet de réutiliser un template sur plusieurs pages. Implique un refactor de `index.php`. |
| Types de champs V1 | `text`, `textarea`, `image`, `link` | Le « titre » n'est pas un type : c'est un `text` avec un rendu différent. `color` et le répéteur sont reportés — le répéteur est le morceau le plus coûteux et ne doit pas retarder la mise en service du moteur. |
| Nom et préfixe | Thème **Ironframe**, préfixe **`iron`** (`iron_field()`), text domain `ironframe`, clés de meta `_iron_*` | Préfixe court et neutre, compatible avec une revente en marque blanche. Les clés de meta sont préfixées d'un underscore pour rester masquées de la meta box « Champs personnalisés » native — le client ne doit jamais voir les valeurs brutes. |
| Contrat d'un type | Un type déclare `default`, `sanitize`, `render`, `escape` (et optionnellement `is_filled`, `label_for`) | Ajouter un type reste une seule entrée sur le filtre `iron_field_types`, sans toucher au noyau, à l'admin ni à l'API de lecture. |
| Sortie d'un `textarea` | `nl2br(esc_html())` | Restitue les retours à la ligne du client sans injecter de `<p>` dans le markup écrit par le développeur, contrairement à `wpautop()`. |
| Nettoyage à la lecture | La valeur est re-nettoyée à chaque lecture, pas seulement à l'écriture | Une meta peut être écrite par un import, WP-CLI ou une migration sans passer par le formulaire. Vérifié : une URL `javascript:` écrite directement en base ne produit aucun lien en front. |
| Portée de `default` | S'applique tant que le champ n'a jamais été enregistré, jamais comme repli permanent | Ce que le client voit en admin est toujours ce que le site affiche. Un champ qui se remplit seul après avoir été vidé est un ticket de support. |
| Droit de créer une page | La capacité `create_posts` du type `page` est basculée sur `iron_create_pages` | WordPress utilise `edit_pages` pour créer **et** modifier : impossible de retirer l'un sans l'autre. La bascule donne au core de quoi masquer seul le bouton « Ajouter », l'entrée de menu et l'accès à `post-new.php`. |
| `publish_pages` accordé au client | Oui, malgré les apparences | Sans cette capacité, WordPress rétrograde une page publiée en « en attente de relecture » à chaque enregistrement. Le droit de créer est retiré par la capacité dédiée ci-dessus, pas par celle-ci. |
| Menus d'administration | Liste blanche, pas liste noire | Le client installera des plugins qu'on ne connaît pas, chacun ajoutant ses entrées. Une liste noire serait périmée dès le premier plugin installé. |
| JS tiers | Aucune librairie chargée par défaut | GSAP était servi depuis un CDN : dépendance externe et exposition RGPD, pour un besoin qui n'existe pas sur tous les projets. Le développeur qui en a besoin l'ajoute en local. |
| Stockage des options | Une entrée `wp_options` par champ (`iron_opt_groupe_champ`), en autoload | Symétrique du stockage des champs de page, et lisible en base. L'autoload se justifie : ces valeurs sont lues sur presque toutes les pages. |
| Écran des options | Formulaire maison posté vers `admin-post.php`, pas la Settings API | La Settings API passe par `options.php`, verrouillé sur `manage_options` — capacité que le client ne doit pas avoir. Le formulaire maison réutilise les gardes déjà éprouvées de la Brique 2. |
| Accès aux options | Capacité dédiée `iron_edit_options` | Le client doit pouvoir changer le téléphone du site sans obtenir au passage l'accès aux réglages de WordPress. |
| Ordre des sections | Ordre de déclaration dans le fichier, puis ordre alphabétique des fichiers | Le développeur contrôle la présentation en écrivant son schéma, sans clé `order` à maintenir. |

## 7. Points encore ouverts

*(aucun point bloquant — les décisions ouvertes sont tranchées au fil des briques)*

## 8. Méthode de travail avec l'agent

- Validation **brique par brique**, sans enchaînement automatique.
- Ce document sert de référence de contexte à chaque session, en complément du
  `CLAUDE.md` à la racine du projet.
- Toute décision structurante prise en session est reportée en §6 ; tout point
  laissé ouvert est reporté en §7.
