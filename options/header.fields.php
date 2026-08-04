<?php
/**
 * En-tête du site : logo et navigation.
 *
 * Pourquoi ici et pas dans les menus natifs de WordPress : l'écran des menus
 * exige la capacité `edit_theme_options`, qui ouvre aussi le personnalisateur,
 * les widgets et le changement de thème. La donner au client reviendrait à
 * rouvrir tout ce que la Brique 5 ferme.
 *
 * Une liste répétable de liens donne le même service, sans aucune capacité
 * supplémentaire, et dans un cadre que le développeur maîtrise : le client
 * peut ajouter, renommer et réordonner des entrées, il ne peut rien casser
 * d'autre.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

return [

    'header' => [
        'label'  => 'En-tête du site',
        'fields' => [

            'logo' => [
                'type'  => 'image',
                'label' => 'Logo',
                'desc'  => 'À défaut, le nom du site est affiché.',
            ],

            'items' => [
                'type'      => 'repeater',
                'label'     => 'Menu principal',
                'desc'      => 'Huit entrées au maximum. Utilisez les flèches pour changer l\'ordre.',
                'max'       => 8,
                'label_add' => 'Ajouter une entrée',
                'label_row' => 'Entrée',
                'fields'    => [
                    'link' => [
                        'type'  => 'link',
                        'label' => 'Lien',
                        'desc'  => 'Le texte saisi est celui qui apparaît dans le menu.',
                    ],
                ],
            ],

        ],
    ],

];
