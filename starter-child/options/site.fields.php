<?php
/**
 * Schéma des options globales du site.
 *
 * Ces données n'appartiennent à aucune page : elles sont utilisées partout.
 * Le développeur ajoute ici les champs dont le projet a besoin ; le client ne
 * peut remplir que ceux-là.
 *
 * Tout fichier `options/*.fields.php` est découvert automatiquement et fusionné
 * dans le même écran d'administration. On peut donc éclater les déclarations
 * par thématique — la seule contrainte est que les identifiants de groupe
 * restent uniques d'un fichier à l'autre.
 *
 * Types disponibles : text | textarea | image | link
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

return [

    'contact' => [
        'label'  => 'Coordonnées',
        'fields' => [
            'phone' => [
                'type'  => 'text',
                'label' => 'Téléphone',
                'desc'  => 'Tel qu\'il doit s\'afficher, par exemple 01 23 45 67 89.',
            ],
            'email' => [
                'type'  => 'text',
                'label' => 'Adresse e-mail',
            ],
            'address' => [
                'type'  => 'textarea',
                'label' => 'Adresse postale',
                'desc'  => 'Les retours à la ligne sont conservés.',
            ],
        ],
    ],

    'social' => [
        'label'  => 'Réseaux sociaux',
        'fields' => [
            'facebook' => [
                'type'  => 'link',
                'label' => 'Facebook',
            ],
            'instagram' => [
                'type'  => 'link',
                'label' => 'Instagram',
            ],
            'linkedin' => [
                'type'  => 'link',
                'label' => 'LinkedIn',
            ],
        ],
    ],

    'footer' => [
        'label'  => 'Pied de page',
        'fields' => [
            'text' => [
                'type'  => 'textarea',
                'label' => 'Texte du pied de page',
            ],
            'legal' => [
                'type'  => 'link',
                'label' => 'Mentions légales',
                'desc'  => 'Lien vers la page de mentions légales.',
            ],
        ],
    ],

];
