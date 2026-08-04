<?php
/**
 * Schéma utilisé par la suite de tests.
 *
 * Volontairement séparé de `pages/home.fields.php`, qui est un exemple destiné
 * à être remplacé projet par projet : les tests ne doivent pas casser parce
 * qu'un développeur a personnalisé la page d'accueil de son client.
 *
 * Ce fichier couvre tous les types et toutes les options du moteur. Le compléter
 * quand un type est ajouté.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

return [

    'basic' => [
        'label'  => 'Champs simples',
        'fields' => [
            'title' => [
                'type'     => 'text',
                'label'    => 'Titre',
                'required' => true,
            ],
            'text' => [
                'type'  => 'textarea',
                'label' => 'Texte',
            ],
            'image' => [
                'type'  => 'image',
                'label' => 'Image',
            ],
            'cta' => [
                'type'  => 'link',
                'label' => 'Lien',
            ],
            'align' => [
                'type'    => 'select',
                'label'   => 'Alignement',
                'default' => 'left',
                'options' => [
                    'left'  => 'À gauche',
                    'right' => 'À droite',
                ],
            ],
        ],
    ],

    'toggled' => [
        'label'  => 'Section désactivable',
        'toggle' => true,
        'fields' => [
            'note' => [
                'type'     => 'text',
                'label'    => 'Note',
                'required' => true,
            ],
        ],
    ],

    'listing' => [
        'label'  => 'Liste répétable',
        'fields' => [
            'rows' => [
                'type'   => 'repeater',
                'label'  => 'Lignes',
                'max'    => 3,
                'fields' => [
                    'name' => ['type' => 'text',   'label' => 'Nom'],
                    'pic'  => ['type' => 'image',  'label' => 'Image'],
                    'url'  => ['type' => 'link',   'label' => 'Lien'],
                    'kind' => [
                        'type'    => 'select',
                        'label'   => 'Catégorie',
                        'options' => ['a' => 'Type A', 'b' => 'Type B'],
                    ],
                ],
            ],
        ],
    ],

];
