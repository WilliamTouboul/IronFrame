<?php
/**
 * Réglages déclarés uniquement par le faux thème enfant : ils doivent s'ajouter
 * à ceux du moteur, pas les remplacer.
 *
 * @package Ironframe
 */

defined('ABSPATH') || exit;

return [
    'projet' => [
        'label'  => 'Propre au projet',
        'fields' => [
            'marqueur' => ['type' => 'text', 'label' => 'Marqueur'],
        ],
    ],
];
