<?php
/**
 * Schéma du gabarit « Produits ».
 *
 * Exemple intermédiaire. Il montre les trois mécanismes que le client
 * manipulera le plus souvent :
 *
 *   - une section désactivable, pour un bandeau saisonnier ;
 *   - une liste répétable, pour un catalogue ;
 *   - une liste de choix à l'intérieur d'une ligne, pour piloter un rendu.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

return [

    'intro' => [
        'label'  => 'Introduction',
        'fields' => [
            'title' => [
                'type'     => 'text',
                'label'    => 'Titre de la page',
                'required' => true,
                'default'  => 'Nos produits',
            ],
            'text' => [
                'type'  => 'textarea',
                'label' => 'Texte d\'introduction',
            ],
        ],
    ],

    // Le client coche pour afficher le bandeau, décoche pour le masquer. Son
    // contenu reste en base et revient tel quel à la réactivation.
    'annonce' => [
        'label'        => 'Bandeau d\'annonce',
        'toggle'       => true,
        'toggle_default' => false,
        'label_toggle' => 'Afficher le bandeau sur la page',
        'fields'       => [
            'title' => [
                'type'     => 'text',
                'label'    => 'Message',
                'required' => true,
                'desc'     => 'Par exemple : « Livraison offerte jusqu\'au 31 décembre ».',
            ],
            'cta' => [
                'type'  => 'link',
                'label' => 'Bouton',
            ],
        ],
    ],

    'catalogue' => [
        'label'  => 'Catalogue',
        'fields' => [

            'title' => [
                'type'    => 'text',
                'label'   => 'Titre de section',
                'default' => 'Notre sélection',
            ],

            'items' => [
                'type'      => 'repeater',
                'label'     => 'Produits',
                'desc'      => 'Douze produits au maximum. Les flèches changent l\'ordre d\'affichage.',
                'max'       => 12,
                'label_add' => 'Ajouter un produit',
                'label_row' => 'Produit',
                'fields'    => [

                    'name' => [
                        'type'  => 'text',
                        'label' => 'Nom du produit',
                    ],

                    'image' => [
                        'type'  => 'image',
                        'label' => 'Photo',
                    ],

                    'description' => [
                        'type'  => 'textarea',
                        'label' => 'Description',
                    ],

                    'price' => [
                        'type'  => 'text',
                        'label' => 'Prix',
                        'desc'  => 'Tel qu\'il doit s\'afficher, devise comprise. Par exemple : 24,90 €.',
                    ],

                    // La valeur stockée est la clé : c'est elle qui devient une
                    // classe CSS sur la carte.
                    'badge' => [
                        'type'    => 'select',
                        'label'   => 'Mise en avant',
                        'options' => [
                            'nouveau' => 'Nouveauté',
                            'promo'   => 'En promotion',
                            'rupture' => 'Bientôt épuisé',
                        ],
                    ],

                    'link' => [
                        'type'  => 'link',
                        'label' => 'En savoir plus',
                    ],

                ],
            ],

        ],
    ],

];
