<?php
/**
 * Faux thème enfant, pour vérifier qu'un fichier du projet remplace bien celui
 * du moteur. Porte volontairement le même nom qu'un gabarit livré.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

return [
    'depuis_lenfant' => [
        'label'  => 'Déclaré par le thème enfant',
        'fields' => [
            'marqueur' => ['type' => 'text', 'label' => 'Marqueur'],
        ],
    ],
];
