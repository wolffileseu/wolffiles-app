<?php

/**
 * Weapon Stat enum mapping — mirrors `weaponStat_t` from
 * ETLegacy's src/game/bg_public.h (extWeaponStats_t enum, 2026-04).
 *
 * Indexed by bit position in the weapon_mask of a 'ws' packet.
 * Values are returned as [code, name, class_hint, team_hint]:
 *   - code:  the C-enum identifier (WS_MP40, WS_THOMPSON, ...)
 *   - name:  human-readable name for UI
 *   - class: which class typically uses this (soldier/medic/engineer/field_ops/covert_ops/any)
 *   - team:  axis / allies / both
 *
 * NOTE: WS_MAX = 28, so valid bits are 0..27.
 */

return [
    0  => ['code' => 'WS_KNIFE',           'name' => 'Knife',             'class' => 'any',        'team' => 'both'],
    1  => ['code' => 'WS_KNIFE_KBAR',      'name' => 'Knife (K-bar)',     'class' => 'any',        'team' => 'allies'],
    2  => ['code' => 'WS_LUGER',           'name' => 'Luger',             'class' => 'any',        'team' => 'axis'],
    3  => ['code' => 'WS_COLT',            'name' => 'Colt',              'class' => 'any',        'team' => 'allies'],
    4  => ['code' => 'WS_MP40',            'name' => 'MP40',              'class' => 'any',        'team' => 'axis'],
    5  => ['code' => 'WS_THOMPSON',        'name' => 'Thompson',          'class' => 'any',        'team' => 'allies'],
    6  => ['code' => 'WS_STEN',            'name' => 'Sten',              'class' => 'covert_ops', 'team' => 'allies'],
    7  => ['code' => 'WS_FG42',            'name' => 'FG42',              'class' => 'any',        'team' => 'both'],
    8  => ['code' => 'WS_PANZERFAUST',     'name' => 'Panzerfaust',       'class' => 'soldier',    'team' => 'axis'],
    9  => ['code' => 'WS_BAZOOKA',         'name' => 'Bazooka',           'class' => 'soldier',    'team' => 'allies'],
    10 => ['code' => 'WS_FLAMETHROWER',    'name' => 'Flamethrower',      'class' => 'soldier',    'team' => 'both'],
    11 => ['code' => 'WS_GRENADE',         'name' => 'Grenade',           'class' => 'any',        'team' => 'both'],
    12 => ['code' => 'WS_MORTAR',          'name' => 'Mortar',            'class' => 'soldier',    'team' => 'allies'],
    13 => ['code' => 'WS_MORTAR2',         'name' => 'Granatwerfer',      'class' => 'soldier',    'team' => 'axis'],
    14 => ['code' => 'WS_DYNAMITE',        'name' => 'Dynamite',          'class' => 'engineer',   'team' => 'both'],
    15 => ['code' => 'WS_AIRSTRIKE',       'name' => 'Airstrike',         'class' => 'field_ops',  'team' => 'both'],
    16 => ['code' => 'WS_ARTILLERY',       'name' => 'Artillery',         'class' => 'field_ops',  'team' => 'both'],
    17 => ['code' => 'WS_SATCHEL',         'name' => 'Satchel',           'class' => 'covert_ops', 'team' => 'both'],
    18 => ['code' => 'WS_GRENADELAUNCHER', 'name' => 'Rifle Grenade',     'class' => 'engineer',   'team' => 'both'],
    19 => ['code' => 'WS_LANDMINE',        'name' => 'Landmine',          'class' => 'engineer',   'team' => 'both'],
    20 => ['code' => 'WS_MG42',            'name' => 'MG42 (Mobile)',     'class' => 'soldier',    'team' => 'axis'],
    21 => ['code' => 'WS_BROWNING',        'name' => 'Browning (Mobile)', 'class' => 'soldier',    'team' => 'allies'],
    22 => ['code' => 'WS_CARBINE',         'name' => 'M1 Garand',         'class' => 'engineer',   'team' => 'allies'],
    23 => ['code' => 'WS_KAR98',           'name' => 'K43 / Kar98',       'class' => 'engineer',   'team' => 'axis'],
    24 => ['code' => 'WS_GARAND',          'name' => 'Scoped Garand',     'class' => 'covert_ops', 'team' => 'allies'],
    25 => ['code' => 'WS_K43',             'name' => 'Scoped K43',        'class' => 'covert_ops', 'team' => 'axis'],
    26 => ['code' => 'WS_MP34',            'name' => 'MP34',              'class' => 'any',        'team' => 'axis'],
    27 => ['code' => 'WS_SYRINGE',         'name' => 'Syringe',           'class' => 'medic',      'team' => 'both'],
];
