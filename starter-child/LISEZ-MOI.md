# Thème enfant — à copier, pas à activer d'ici

Ce dossier est un **modèle**. Il ne fonctionne pas là où il se trouve.

## En trois gestes

1. Copiez ce dossier dans `wp-content/themes/`, à côté de `ironframe`.
2. Renommez-le d'après le projet, par exemple `boulangerie-martin`.
3. Activez-le dans **Apparence → Thèmes**. Vous activez l'**enfant**, jamais le
   parent.

C'est tout. Le moteur est déjà là.

## Où va quoi

| Dossier | Contenu | Qui le met à jour |
|---|---|---|
| `ironframe/` | Le moteur : champs, rôle client, réglages | Ironframe, par mise à jour |
| Votre dossier | Gabarits, schémas, styles, images | Vous |

**Ne modifiez jamais `ironframe/`.** Une mise à jour écraserait votre travail.
Tout se personnalise depuis l'enfant.

## Reprendre un gabarit livré

Copiez `ironframe/pages/accueil.php` et `accueil.fields.php` dans le `pages/` de
votre thème enfant, puis modifiez-les. Un fichier de l'enfant remplace celui du
parent qui porte le même nom : il n'y a rien à désinscrire.

La même règle vaut pour `options/`, `templates/` et `assets/`.

## Modifier le comportement du moteur

Par les filtres, depuis `functions.php` — jamais en éditant `inc/`. Les
exemples les plus courants y sont déjà, en commentaire.
