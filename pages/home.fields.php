<?php

/**
 * Schéma de champs du template `pages/home.php`.
 *
 * Le rattachement se fait par le nom du fichier : `home.php` -> `home.fields.php`.
 * Aucun enregistrement à écrire ailleurs.
 *
 * Clés reconnues sur un champ :
 *   type    (obligatoire) text | textarea | image | link
 *   label   (obligatoire en pratique, sinon déduit de l'identifiant)
 *   desc    (optionnel) aide affichée sous le champ en admin
 *   default (optionnel) valeur pré-remplie tant que la page n'a jamais été
 *           enregistrée. Ce n'est PAS un repli permanent : si le client vide le
 *           champ, le front affiche vide. Ce que le client voit en admin est
 *           toujours ce que le site affiche.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

return [

    // Section désactivable : le client coche pour l'afficher, décoche pour la
    // masquer. Typiquement un bandeau saisonnier.
    'promo' => [
        'label'        => 'Bandeau de promotion',
        'toggle'       => true,
        'label_toggle' => 'Afficher le bandeau sur le site',
        'fields'       => [
            'title' => [
                'type'     => 'text',
                'label'    => 'Message',
                'required' => true,
            ],
            'cta' => [
                'type'  => 'link',
                'label' => 'Bouton',
            ],
        ],
    ],

    'hero' => [
        'label'  => 'Bannière',
        'fields' => [
            'title' => [
                'type'     => 'text',
                'label'    => 'Titre principal',
                'required' => true,
            ],
            'text' => [
                'type'  => 'textarea',
                'label' => 'Accroche',
                'desc'  => 'Deux lignes maximum. Evite de marquer n\'importe quoi',
            ],
            'formulaire' => [
                'type'  => 'textarea',
                'label' => 'Formulaire',
                'desc'  => 'Contenu du formulaire.',
            ],
            'image' => [
                'type'  => 'image',
                'label' => 'Visuel de fond',
            ],
            'cta' => [
                'type'  => 'link',
                'label' => 'Bouton',
            ],
        ],
    ],

    'services' => [
        'label'  => 'Nos services',
        'fields' => [
            'title' => [
                'type'    => 'text',
                'label'   => 'Titre de section',
                'default' => 'Nos services',
            ],

            // Liste répétable : le client ajoute, réordonne et supprime des
            // lignes, mais ne décide ni des sous-champs ni du nombre maximum.
            'items' => [
                'type'      => 'repeater',
                'label'     => 'Prestations',
                'desc'      => 'Six prestations au maximum.',
                'max'       => 6,
                'label_add' => 'Ajouter une prestation',
                'label_row' => 'Prestation',
                'fields'    => [
                    'title' => [
                        'type'  => 'text',
                        'label' => 'Intitulé',
                    ],
                    'text' => [
                        'type'  => 'textarea',
                        'label' => 'Description',
                    ],
                    'image' => [
                        'type'  => 'image',
                        'label' => 'Illustration',
                    ],
                    'cta' => [
                        'type'  => 'link',
                        'label' => 'En savoir plus',
                    ],
                ],
            ],
        ],
    ],

    'cartes' => [
        'label'  => 'Nos cartes',
        'fields' => [
            'title' => [
                'type'    => 'text',
                'label'   => 'Titre de section',
                'default' => 'Nos cartes',
            ],
        ],
    ],

];
