<?php
/**
 * Schéma du gabarit « Contact ».
 *
 * Volontairement pauvre : l'essentiel du contenu de cette page ne lui
 * appartient pas. Téléphone, adresse, e-mail et réseaux sociaux vivent dans
 * « Réglages du site », se saisissent une seule fois et s'affichent partout.
 *
 * On ne déclare donc ici que ce qui est propre à la page.
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
                'default'  => 'Nous contacter',
            ],
            'text' => [
                'type'  => 'textarea',
                'label' => 'Texte d\'introduction',
            ],
        ],
    ],

    'pratique' => [
        'label'  => 'Informations pratiques',
        'fields' => [
            'horaires' => [
                'type'  => 'textarea',
                'label' => 'Horaires d\'ouverture',
                'desc'  => 'Une ligne par jour ou par plage. Les retours à la ligne sont conservés.',
            ],
            'acces' => [
                'type'  => 'textarea',
                'label' => 'Accès',
                'desc'  => 'Transports, stationnement, étage.',
            ],
            'plan' => [
                'type'  => 'image',
                'label' => 'Plan d\'accès',
                'desc'  => 'Une capture de carte, si vous ne souhaitez pas d\'iframe externe.',
            ],
        ],
    ],

];
