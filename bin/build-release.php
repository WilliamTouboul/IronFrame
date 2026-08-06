<?php
/**
 * Fabrique l'archive de distribution et le fichier de version.
 *
 * Ce n'est PAS une étape de construction du thème : Ironframe s'installe
 * toujours en copiant un dossier, et rien de ce qui suit n'est nécessaire pour
 * l'utiliser. C'est un outil pour l'éditeur, pas pour le client.
 *
 * Il applique la liste d'exclusions décidée une fois pour toutes, plutôt que de
 * la refaire de mémoire à chaque publication — c'est exactement le genre de
 * tâche où l'on finit par livrer ses tests ou son document d'architecture.
 *
 * Usage :
 *
 *     php bin/build-release.php https://exemple.fr/telechargements/
 *
 * L'argument est l'adresse **du dossier** où l'archive sera déposée. Il sert à
 * écrire le `package` du fichier de version. Sans lui, un emplacement fictif
 * est utilisé et le script le signale.
 *
 * @package Ironframe
 */

if ('cli' !== PHP_SAPI) {
    exit("Ce script ne s'exécute qu'en ligne de commande.\n");
}

if (!class_exists('ZipArchive')) {
    exit("L'extension zip de PHP est nécessaire.\n");
}

$racine = dirname(__DIR__);
$nom    = basename($racine);
$sortie = $racine . '/dist';

/*
 * Ce qui ne part jamais chez le client.
 *
 * `pages/` est exclu en entier : depuis la séparation moteur / projet, le
 * moteur ne contient plus aucun gabarit. Ceux du jeu de départ voyagent dans
 * `starter-child/`.
 */
$exclusions = [
    '.git',
    '.gitignore',
    'bin',
    'dist',
    'docs',
    'tests',
    'pages',
];

/* -------------------------------------------------------------------------- */

/**
 * Lit la version dans l'en-tête de `style.css`.
 *
 * @param string $racine
 * @return string
 */
function iron_lire_version($racine)
{
    $entete = file_get_contents($racine . '/style.css');

    return preg_match('/^\s*Version:\s*(.+)$/mi', $entete, $m)
        ? trim($m[1])
        : '';
}

/**
 * Le chemin doit-il être écarté ?
 *
 * @param string   $relatif
 * @param string[] $exclusions
 * @return bool
 */
function iron_est_exclu($relatif, array $exclusions)
{
    $premier = explode('/', $relatif)[0];

    return in_array($premier, $exclusions, true);
}

$version = iron_lire_version($racine);

if ('' === $version) {
    exit("Version introuvable dans style.css.\n");
}

$base    = isset($argv[1]) ? rtrim($argv[1], '/') . '/' : '';
$fictif  = '' === $base;
$base    = $fictif ? 'https://exemple.invalid/telechargements/' : $base;
$archive = sprintf('%s-%s.zip', $nom, $version);

if (!is_dir($sortie) && !mkdir($sortie, 0755, true)) {
    exit("Impossible de créer $sortie\n");
}

if (file_exists($sortie . '/' . $archive)) {
    unlink($sortie . '/' . $archive);
}

$zip = new ZipArchive();

if (true !== $zip->open($sortie . '/' . $archive, ZipArchive::CREATE)) {
    exit("Impossible d'ouvrir l'archive en écriture.\n");
}

$iterateur = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($racine, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$inclus = 0;
$ecarte = [];

foreach ($iterateur as $fichier) {

    $relatif = str_replace('\\', '/', substr($fichier->getPathname(), strlen($racine) + 1));

    if (iron_est_exclu($relatif, $exclusions)) {
        $ecarte[explode('/', $relatif)[0]] = true;
        continue;
    }

    /*
     * Les dossiers ne sont pas ajoutés explicitement : ZipArchive les crée en
     * même temps que les fichiers qu'ils contiennent. Un dossier vidé par la
     * séparation moteur / projet ne se retrouve donc pas dans l'archive à
     * intriguer celui qui l'ouvre.
     */
    if ($fichier->isDir()) {
        continue;
    }

    // L'archive doit contenir un dossier de thème à sa racine : c'est ce que
    // l'installateur de WordPress attend.
    $zip->addFile($fichier->getPathname(), $nom . '/' . $relatif);
    $inclus++;
}

$zip->close();

/* -------------------------------------------------------------------------- */

$json = [
    'version'      => $version,
    'package'      => $base . $archive,
    'url'          => '',
    'requires'     => '6.0',
    'requires_php' => '7.4',
];

file_put_contents(
    $sortie . '/version.json',
    wp_json_encode_compat($json) . "\n"
);

/**
 * Encodage lisible, sans dépendre de WordPress.
 *
 * @param array $donnees
 * @return string
 */
function wp_json_encode_compat(array $donnees)
{
    return json_encode($donnees, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

printf("Archive   : dist/%s  (%d fichiers, %d Ko)\n", $archive, $inclus, (int) (filesize($sortie . '/' . $archive) / 1024));
printf("Version   : dist/version.json\n");
printf("Écartés   : %s\n", implode(', ', array_keys($ecarte)));

if ($fictif) {
    printf("\nATTENTION : aucune adresse fournie, `package` pointe vers un emplacement fictif.\n");
    printf("            Relancez avec : php bin/build-release.php https://votre-site.fr/telechargements/\n");
}

$readme = file_get_contents($racine . '/README.md');

if (false !== strpos($readme, 'ironframe.example')) {
    printf("\nATTENTION : README.md contient encore l'URL de documentation fictive.\n");
}
