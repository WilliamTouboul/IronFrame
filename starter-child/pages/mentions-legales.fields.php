<?php
/**
 * Schéma du gabarit « Mentions légales ».
 *
 * Obligatoires en France pour tout site professionnel, et quasiment identiques
 * d'un projet à l'autre : autant les livrer.
 *
 * Le contenu est découpé en sections répétables plutôt qu'en un seul bloc de
 * texte. Ironframe ne propose pas d'éditeur riche — c'est un choix, pas un
 * oubli — donc un unique champ long ne permettrait aucun intertitre. Une
 * section par rubrique donne la même souplesse, avec une structure que le
 * client ne peut pas casser.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

return [

    'page' => [
        'label'  => 'En-tête',
        'fields' => [
            'title' => [
                'type'     => 'text',
                'label'    => 'Titre de la page',
                'required' => true,
                'default'  => 'Mentions légales',
            ],
            'maj' => [
                'type'  => 'text',
                'label' => 'Dernière mise à jour',
                'desc'  => 'Par exemple : janvier 2026.',
            ],
        ],
    ],

    'contenu' => [
        'label'  => 'Rubriques',
        'fields' => [
            'sections' => [
                'type'      => 'repeater',
                'label'     => 'Sections',
                'desc'      => 'Une section par rubrique : éditeur, hébergeur, propriété intellectuelle, données personnelles, cookies.',
                'max'       => 20,
                'label_add' => 'Ajouter une section',
                'label_row' => 'Section',
                'fields'    => [
                    'title' => [
                        'type'  => 'text',
                        'label' => 'Intertitre',
                    ],
                    'text' => [
                        'type'  => 'textarea',
                        'label' => 'Texte',
                    ],
                ],
            ],
        ],
    ],

];
