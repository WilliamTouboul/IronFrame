# Mises à jour Notion en attente

Fichier **temporaire**. Le connecteur Notion s'est déconnecté en cours de
session ; ces sections n'ont donc pas pu être appliquées à la documentation en
ligne. À supprimer une fois reportées.

Deux façons de s'en servir : les coller directement dans Notion, qui importe le
Markdown nativement, ou me demander de les appliquer au début d'une prochaine
session, une fois le connecteur revenu.

---

## → Page « 3. Les types de champs »

**Mettre à jour le tableau de tête** : il annonce cinq types, il y en a six.
Ajouter une ligne `select` — « Liste déroulante » — « Une clé parmi celles que
vous déclarez ».

**Ajouter cette section, avant `repeater` :**

### select — liste de choix

Pour tout ce qui pilote un rendu plutôt qu'un contenu : un alignement, une
variante de couleur, un nombre de colonnes.

```php
'align' => [
    'type'    => 'select',
    'label'   => 'Alignement du contenu',
    'default' => 'left',
    'options' => [
        'left'   => 'À gauche',
        'center' => 'Centré',
        'right'  => 'À droite',
    ],
],
```

```php
<section class="hero hero--<?= iron_field('hero.align') ?>">
```

La valeur stockée est la **clé**, pas le libellé : c'est elle qu'on injecte dans
une classe CSS. Le libellé n'existe que pour le client, dans l'écran d'édition.

Quelle que soit la valeur envoyée par le navigateur, seule une clé réellement
déclarée dans `options` entre en base. C'est ce qui rend le type sûr.

Une option vide est toujours proposée en tête. Sans elle, un champ jamais touché
prendrait silencieusement la première valeur de la liste, et le client n'aurait
aucun moyen de revenir en arrière. Si le champ est `required`, la validation
refusera ce choix vide.

Le type fonctionne aussi comme sous-champ d'une liste répétable.

---

## → Page « 6. Les réglages du site »

**Ajouter cette section :**

### Le menu du site

Ironframe n'utilise **pas** les menus natifs de WordPress. Leur écran exige la
capacité `edit_theme_options`, qui ouvre aussi le personnalisateur, les widgets
et le changement de thème — la donner au client rouvrirait tout ce que le rôle
Client ferme.

À la place, `options/header.fields.php` déclare le logo et une liste répétable
de liens. Le client ajoute, renomme et réordonne ses entrées avec les mêmes
flèches que partout ailleurs, sans aucune capacité supplémentaire.

Le rendu se trouve dans `templates/header_on_site.php`. À défaut de logo, le nom
du site est affiché.

Pour ajouter une entrée au menu, le client passe par **Réglages du site →
En-tête du site**. Pour en changer la structure, c'est au développeur de
modifier le schéma.

---

## → Nouvelle page « 9. Sécurité du contenu et diagnostic »

À créer après « 8. Référence de l'API ».

### Les révisions des champs

Les révisions natives de WordPress portent sur le titre et le contenu, jamais
sur les champs personnalisés. Sans traitement particulier, un client qui
supprime cinq lignes d'une liste répétable et enregistre les perd
définitivement.

Ironframe déclare toutes ses clés au mécanisme de métadonnées révisionnées
introduit par WordPress 6.4. Concrètement :

- chaque enregistrement crée une révision, **même si seul un champ a changé** ;
- restaurer une révision restaure les valeurs des champs, l'état des sections
  désactivables compris.

Le client ne voit pas la boîte des révisions : c'est vous qui restaurez, à sa
demande. C'est volontaire — il ne doit pas pouvoir revenir en arrière sans
comprendre ce qu'il défait.

Si vos gabarits sont rangés ailleurs que dans `pages/`, déclarez leurs clés avec
le filtre `iron_revisioned_meta_keys`.

### Le panneau de diagnostic

Sous `WP_DEBUG`, et pour un utilisateur capable de gérer les options, une entrée
**Ironframe** apparaît dans la barre d'administration — en front comme sur
l'écran d'édition.

Elle répond d'un coup d'œil aux trois questions habituelles : quel gabarit sert
cette page, quels champs existent, et que contiennent-ils réellement. Une liste
répétable s'y résume en « 3 lignes », une image en « média #15 », et une section
masquée est signalée comme telle.

Rien n'apparaît en production.

### La colonne Template

La liste des pages affiche le gabarit de chaque page et son nombre de champs.
Un gabarit supprimé du thème est signalé « introuvable » plutôt que de laisser
une case vide. La colonne est masquée au client.

---

## → Page « 5. Les listes répétables »

**Ajouter, dans « Ce que voit le client » :**

Supprimer une ligne qui contient quelque chose demande une confirmation. Une
ligne encore vide se supprime sans question : confirmer pour rien apprend à
cliquer « oui » sans lire, et la confirmation ne protège alors plus rien le jour
où elle compte.

---

## → Page d'accueil « Ironframe »

Dans le tableau « En un coup d'œil », remplacer la ligne **Types de champs** par :

`texte court, texte long, image, lien, liste de choix, liste répétable`
