<?php

/**
 * ET player class enum mapping.
 *
 * Indexed by the class value from tracker packets (matches the C enum
 * PC_SOLDIER = 0, PC_MEDIC = 1, ... from ETLegacy's bg_public.h).
 *
 * Icons extracted from pak0.pk3 (gfx/limbo/skill_*.tga -> PNG).
 * These are the original ET 2003 class badges, converted to PNG for web.
 */

return [
    0 => [
        'code' => 'PC_SOLDIER',
        'name' => 'Soldier',
        'short' => 'S',
        'icon' => 'skill_soldier.png',
        'color' => '#ef4444', // red
    ],
    1 => [
        'code' => 'PC_MEDIC',
        'name' => 'Medic',
        'short' => 'M',
        'icon' => 'skill_medic.png',
        'color' => '#22c55e', // green
    ],
    2 => [
        'code' => 'PC_ENGINEER',
        'name' => 'Engineer',
        'short' => 'E',
        'icon' => 'skill_engineer.png',
        'color' => '#f59e0b', // amber
    ],
    3 => [
        'code' => 'PC_FIELDOPS',
        'name' => 'Field Ops',
        'short' => 'F',
        'icon' => 'skill_fieldops.png',
        'color' => '#3b82f6', // blue
    ],
    4 => [
        'code' => 'PC_COVERTOPS',
        'name' => 'Covert Ops',
        'short' => 'C',
        'icon' => 'skill_covops.png',
        'color' => '#a855f7', // purple
    ],
];
