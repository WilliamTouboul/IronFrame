# Manifeste

Ironframe est né d'un constat désagréable : sur la plupart des sites que nous
livrons, ce qui casse n'est presque jamais notre code. C'est une extension qui
change de mainteneur, une mise à jour qui se passe mal, ou un client bien
intentionné qui déplace un bloc.

Nous n'avons pas construit un thème avec plus de possibilités. Nous en avons
construit un avec **moins**, et nous assumons chacune d'elles.

---

## Le client ne peut pas casser ce qu'on ne lui a pas donné

Le développeur déclare en PHP les champs éditables d'une page. Le client
remplit ceux-là, et rien d'autre. Il n'a accès ni à la mise en page, ni aux
extensions, ni au thème, ni à la structure de ses pages.

Ce n'est pas de la défiance envers lui. C'est reconnaître qu'on ne lui a jamais
promis d'être développeur, et qu'on n'a aucune raison de le mettre en position
de se tromper.

**Ce que nous abandonnons :** le client ne peut pas créer une page seul. Elle
passera par vous.

## La contrainte est la fonctionnalité

Il n'y a pas d'éditeur riche. Pas de constructeur de pages. Pas de champ
« contenu libre » où l'on colle du HTML.

Un site livré avec Ironframe ressemble, six mois plus tard, à ce qu'il était le
jour de la livraison. C'est l'essentiel de ce que nous vendons.

**Ce que nous abandonnons :** un client qui veut mettre un mot en gras au milieu
d'un paragraphe devra vous le demander.

## Ne dépendre de personne pour l'essentiel

Le système de champs n'utilise que les fonctions natives de WordPress. Aucune
extension, aucune librairie, aucun paquet à installer. Il ne peut donc être ni
racheté, ni abandonné, ni compromis par un tiers.

Cela ne vise personne : les extensions de champs qui nous ont précédés sont
excellentes. Le problème n'a jamais été leur qualité, mais le fait que la
fonction centrale de nos sites dépendait d'une décision qui nous échappait.

En revanche, Ironframe reste **pleinement compatible** avec les extensions que
vous voudrez installer — formulaires, référencement, multilingue, sauvegardes.
Nous ne réinternalisons que ce dont nous dépendions, jamais ce que d'autres font
mieux.

**Ce que nous abandonnons :** l'écosystème d'add-ons qu'offre une grande
extension. Ici, ce que vous ajoutez, vous l'écrivez.

## Aucune étape de construction

Le thème s'installe en copiant un dossier. Pas de compilation, pas de gestion de
paquets, pas d'outillage à maintenir.

Un site livré aujourd'hui se reprend dans cinq ans avec un éditeur de texte et
un client FTP. C'est une garantie que peu de produits modernes peuvent donner.

**Ce que nous abandonnons :** le confort d'un outillage moderne côté
développement.

## La sécurité est une direction, pas une étape

Tout ce que l'API renvoie est déjà échappé. Sortir du contenu brut demande un
appel différent, explicite, que l'on voit dans une relecture de code.

Les valeurs sont nettoyées à l'écriture **et à la lecture** : une donnée arrivée
par un import, une migration ou une écriture directe en base est traitée avec la
même méfiance qu'une saisie de formulaire.

Les droits du rôle client sont la vraie frontière. Ce qui est masqué dans
l'interface ne fait qu'éviter le mauvais clic ; ce n'est jamais ce qui protège.

## Le site d'un client ne doit jamais tomber

Une déclaration de champ erronée prive le client d'un champ. Elle ne provoque
pas d'erreur fatale, et le site continue de fonctionner.

Un champ obligatoire vidé par mégarde sur une page en ligne conserve sa valeur
précédente et affiche un message. La page **n'est jamais dépubliée** : mettre un
site hors service pour une faute de frappe serait un remède pire que le mal.

## Ce que le client voit est ce que le site affiche

Pas de valeur de repli qui réapparaît toute seule. Pas de champ qui se remplit
après avoir été vidé. Pas de comportement qui diffère entre l'écran d'édition et
la page publiée.

La prévisibilité vaut mieux que l'intelligence. Un système qui devine se trompe,
et personne ne sait pourquoi.

## Ce que vous écrivez ne sera jamais écrasé

Le moteur et votre projet sont deux dossiers distincts. Le premier se met à
jour, le second vous appartient. Aucune mise à jour ne touchera vos gabarits,
vos schémas ni vos styles.

Et parce qu'un accident reste possible, les valeurs des champs sont
révisionnées : ce qu'un client supprime, vous pouvez le restaurer.

---

## Ce qu'Ironframe n'est pas

- **Un constructeur de pages.** Si votre client doit composer ses mises en page,
  ce produit n'est pas pour lui.
- **Un thème générique.** C'est un socle à personnaliser projet par projet, pas
  un thème que l'on installe et que l'on habille par des réglages.
- **Un produit sans développeur.** Il faut écrire du PHP pour déclarer un champ.
  C'est délibéré : c'est ce qui rend le résultat prévisible.

## À qui il s'adresse

Aux développeurs et aux agences qui livrent des sites vitrines à des clients qui
n'ont ni le temps ni l'envie d'apprendre WordPress — et qui préfèrent un site
que personne ne peut abîmer à un site que tout le monde peut modifier.

---

*Chaque comportement décrit ici est couvert par la suite de tests livrée avec le
thème. Les raisons techniques de ces choix sont consignées dans
`docs/ARCHITECTURE.md`.*
