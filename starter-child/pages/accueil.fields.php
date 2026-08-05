<?php
/**
 * Schéma du gabarit « Accueil ».
 *
 * L'exemple le plus simple possible : un seul groupe, trois champs. À lire en
 * premier pour comprendre la mécanique, avant `produit.fields.php` qui montre
 * les listes répétables et les sections désactivables.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

return [

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
                'desc'  => 'Deux ou trois lignes. Les retours à la ligne sont conservés.',
            ],

            'image' => [
                'type'  => 'image',
                'label' => 'Visuel',
            ],

        ],
    ],

];
