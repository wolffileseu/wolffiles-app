<?php

/**
 * Weapon Stat enum mapping — mirrors `weaponStat_t` from
 * ETLegacy's src/game/bg_public.h (extWeaponStats_t enum, 2026-04).
 *
 * Indexed by bit position in the weapon_mask of a 'ws' packet.
 * Values are:
 *   - code:  the C-enum identifier (WS_MP40, WS_THOMPSON, ...)
 *   - name:  human-readable name for UI
 *   - icon:  filename under /public/img/tracker/weapons/
 *            (extracted from legacy_v2.83.1.pk3)
 *   - class: which class typically uses this (soldier/medic/engineer/field_ops/covert_ops/any)
 *   - team:  axis / allies / both
 *
 * NOTE: WS_MAX = 28, so valid bits are 0..27.
 *
 * Some bits don't have a direct icon equivalent in the shipped SVG set:
 *   - WS_AIRSTRIKE      -> uses smokegrenade (what fieldops throws)
 *   - WS_ARTILLERY      -> uses binoculars (what fieldops uses to call arty)
 *   - WS_GRENADELAUNCHER -> uses kar98_gren (German rifle grenade)
 */

return [
    0  => ['code' => 'WS_KNIFE',           'name' => 'Knife',             'icon' => 'iconw_knife.svg',         'class' => 'any',        'team' => 'both'],
    1  => ['code' => 'WS_KNIFE_KBAR',      'name' => 'Knife (K-bar)',     'icon' => 'iconw_knife_kbar.svg',    'class' => 'any',        'team' => 'allies'],
    2  => ['code' => 'WS_LUGER',           'name' => 'Luger',             'icon' => 'iconw_luger.svg',         'class' => 'any',        'team' => 'axis'],
    3  => ['code' => 'WS_COLT',            'name' => 'Colt',              'icon' => 'iconw_colt.svg',          'class' => 'any',        'team' => 'allies'],
    4  => ['code' => 'WS_MP40',            'name' => 'MP40',              'icon' => 'iconw_mp40.svg',          'class' => 'any',        'team' => 'axis'],
    5  => ['code' => 'WS_THOMPSON',        'name' => 'Thompson',          'icon' => 'iconw_thompson.svg',      'class' => 'any',        'team' => 'allies'],
    6  => ['code' => 'WS_STEN',            'name' => 'Sten',              'icon' => 'iconw_sten.svg',          'class' => 'covert_ops', 'team' => 'allies'],
    7  => ['code' => 'WS_FG42',            'name' => 'FG42',              'icon' => 'iconw_fg42.svg',          'class' => 'any',        'team' => 'both'],
    8  => ['code' => 'WS_PANZERFAUST',     'name' => 'Panzerfaust',       'icon' => 'iconw_panzerfaust.svg',   'class' => 'soldier',    'team' => 'axis'],
    9  => ['code' => 'WS_BAZOOKA',         'name' => 'Bazooka',           'icon' => 'iconw_bazooka.svg',       'class' => 'soldier',    'team' => 'allies'],
    10 => ['code' => 'WS_FLAMETHROWER',    'name' => 'Flamethrower',      'icon' => 'iconw_flamethrower.svg',  'class' => 'soldier',    'team' => 'both'],
    11 => ['code' => 'WS_GRENADE',         'name' => 'Grenade',           'icon' => 'iconw_grenade.svg',       'class' => 'any',        'team' => 'both'],
    12 => ['code' => 'WS_MORTAR',          'name' => 'Mortar',            'icon' => 'iconw_mortar.svg',        'class' => 'soldier',    'team' => 'allies'],
    13 => ['code' => 'WS_MORTAR2',         'name' => 'Granatwerfer',      'icon' => 'iconw_mortar_ax.svg',     'class' => 'soldier',    'team' => 'axis'],
    14 => ['code' => 'WS_DYNAMITE',        'name' => 'Dynamite',          'icon' => 'iconw_dynamite.svg',      'class' => 'engineer',   'team' => 'both'],
    15 => ['code' => 'WS_AIRSTRIKE',       'name' => 'Airstrike',         'icon' => 'iconw_smokegrenade.svg',  'class' => 'field_ops',  'team' => 'both'],
    16 => ['code' => 'WS_ARTILLERY',       'name' => 'Artillery',         'icon' => 'iconw_binoculars.svg',    'class' => 'field_ops',  'team' => 'both'],
    17 => ['code' => 'WS_SATCHEL',         'name' => 'Satchel',           'icon' => 'iconw_satchel.svg',       'class' => 'covert_ops', 'team' => 'both'],
    18 => ['code' => 'WS_GRENADELAUNCHER', 'name' => 'Rifle Grenade',     'icon' => 'iconw_kar98_gren.svg',    'class' => 'engineer',   'team' => 'both'],
    19 => ['code' => 'WS_LANDMINE',        'name' => 'Landmine',          'icon' => 'iconw_landmine.svg',      'class' => 'engineer',   'team' => 'both'],
    20 => ['code' => 'WS_MG42',            'name' => 'MG42 (Mobile)',     'icon' => 'iconw_mg42.svg',          'class' => 'soldier',    'team' => 'axis'],
    21 => ['code' => 'WS_BROWNING',        'name' => 'Browning (Mobile)', 'icon' => 'iconw_browning.svg',      'class' => 'soldier',    'team' => 'allies'],
    22 => ['code' => 'WS_CARBINE',         'name' => 'M1 Garand',         'icon' => 'iconw_m1_garand.svg',     'class' => 'engineer',   'team' => 'allies'],
    23 => ['code' => 'WS_KAR98',           'name' => 'K43 / Kar98',       'icon' => 'iconw_kar98.svg',         'class' => 'engineer',   'team' => 'axis'],
    24 => ['code' => 'WS_GARAND',          'name' => 'Scoped Garand',     'icon' => 'iconw_mauser.svg',        'class' => 'covert_ops', 'team' => 'allies'],
    25 => ['code' => 'WS_K43',             'name' => 'Scoped K43',        'icon' => 'iconw_kar98.svg',         'class' => 'covert_ops', 'team' => 'axis'],
    26 => ['code' => 'WS_MP34',            'name' => 'MP34',              'icon' => 'iconw_mp34.svg',          'class' => 'any',        'team' => 'axis'],
    27 => ['code' => 'WS_SYRINGE',         'name' => 'Syringe',           'icon' => 'iconw_syringe.svg',       'class' => 'medic',      'team' => 'both'],
];
