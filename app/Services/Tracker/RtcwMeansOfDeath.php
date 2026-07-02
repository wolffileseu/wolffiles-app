<?php

namespace App\Services\Tracker;

/**
 * Maps RtCW (iortcw) means_of_death_t enum indices to a weapon key, a human
 * label, and a category. Index order mirrors code/game/bg_public.h of the
 * iortcw MP tree (validated: MOD_AIRSTRIKE == 46, matching the live obituary
 * "Kill: 6 2 46: ... by MOD_AIRSTRIKE").
 *
 * Categories:
 *   weapon       - a real player kill with a weapon -> credit killer a frag
 *   environment  - world damage (lava, falling, crush, trigger) -> no frag
 *   suicide      - self-inflicted -> death only, no frag for anyone
 *   action       - non-kill events that should never arrive as "kill" but are
 *                  mapped defensively (syringe revive, ammo pack, eng/medic)
 *   unknown      - MOD_UNKNOWN / out-of-range
 *
 * Indices 1..11 are leftover Quake3 means-of-death (SHOTGUN..BFG_SPLASH) that
 * RtCW-MP does not use in practice; kept for correct offset only. Indices
 * 62..73 (zombie/loper/bat) are single-player and not expected in MP.
 */
class RtcwMeansOfDeath
{
    /**
     * index => [key, label, category]
     */
    private const MAP = [
        0  => ['unknown',            'Unknown',            'unknown'],

        // Quake3 leftovers (unused in RtCW-MP, present for index alignment)
        1  => ['shotgun',            'Shotgun',            'weapon'],
        2  => ['gauntlet',           'Gauntlet',           'weapon'],
        3  => ['machinegun',         'Machinegun',         'weapon'],
        4  => ['grenade',            'Grenade',            'weapon'],
        5  => ['grenade_splash',     'Grenade (splash)',   'weapon'],
        6  => ['rocket',             'Rocket',             'weapon'],
        7  => ['rocket_splash',      'Rocket (splash)',    'weapon'],
        8  => ['railgun',            'Railgun',            'weapon'],
        9  => ['lightning',          'Lightning',          'weapon'],
        10 => ['bfg',                'BFG',                'weapon'],
        11 => ['bfg_splash',         'BFG (splash)',       'weapon'],

        // RtCW weapons
        12 => ['knife',              'Knife',              'weapon'],
        13 => ['knife2',             'Knife',              'weapon'],
        14 => ['knife_stealth',      'Stealth Knife',      'weapon'],
        15 => ['luger',              'Luger',              'weapon'],
        16 => ['colt',               'Colt',               'weapon'],
        17 => ['mp40',               'MP40',               'weapon'],
        18 => ['thompson',           'Thompson',           'weapon'],
        19 => ['sten',               'Sten',               'weapon'],
        20 => ['mauser',             'Mauser',             'weapon'],
        21 => ['sniperrifle',        'Sniper Rifle',       'weapon'],
        22 => ['garand',             'Garand',             'weapon'],
        23 => ['snooperscope',       'Snooper Scope',      'weapon'],
        24 => ['silencer',           'Silenced Luger',     'weapon'],
        25 => ['akimbo',             'Akimbo Colts',       'weapon'],
        26 => ['bar',                'BAR',                'weapon'],
        27 => ['fg42',               'FG42',               'weapon'],
        28 => ['fg42scope',          'FG42 (scoped)',      'weapon'],
        29 => ['panzerfaust',        'Panzerfaust',        'weapon'],
        30 => ['rocket_launcher',    'Rocket Launcher',    'weapon'],
        31 => ['grenade_launcher',   'Grenade Launcher',   'weapon'],
        32 => ['venom',              'Venom',              'weapon'],
        33 => ['venom_full',         'Venom',              'weapon'],
        34 => ['flamethrower',       'Flamethrower',       'weapon'],
        35 => ['tesla',              'Tesla',              'weapon'],
        36 => ['speargun',           'Speargun',           'weapon'],
        37 => ['speargun_co2',       'Speargun (CO2)',     'weapon'],
        38 => ['grenade_pineapple',  'Pineapple Grenade',  'weapon'],
        39 => ['cross',              'Throwing Knife',     'weapon'],
        40 => ['mortar',             'Mortar',             'weapon'],
        41 => ['mortar_splash',      'Mortar (splash)',    'weapon'],
        42 => ['kicked',             'Kick',               'weapon'],
        43 => ['grabber',            'Grabber',            'weapon'],
        44 => ['dynamite',           'Dynamite',           'weapon'],
        45 => ['dynamite_splash',    'Dynamite (splash)',  'weapon'],
        46 => ['airstrike',          'Airstrike',          'weapon'],
        47 => ['syringe',            'Syringe',            'action'],
        48 => ['ammo',               'Ammo Pack',          'action'],
        49 => ['arty',               'Artillery',          'weapon'],

        // Environment / world
        50 => ['water',              'Drowning',           'environment'],
        51 => ['slime',              'Slime',              'environment'],
        52 => ['lava',               'Lava',               'environment'],
        53 => ['crush',              'Crushed',            'environment'],
        54 => ['telefrag',           'Telefrag',           'environment'],
        55 => ['falling',            'Falling',            'environment'],
        56 => ['suicide',            'Suicide',            'suicide'],
        57 => ['target_laser',       'Laser',              'environment'],
        58 => ['trigger_hurt',       'Trigger',            'environment'],
        59 => ['grapple',            'Grapple',            'environment'],
        60 => ['explosive',          'Explosive',          'environment'],
        61 => ['poisongas',          'Poison Gas',         'environment'],

        // 62..70 zombie/loper (SP) — not expected in MP
        71 => ['engineer',           'Engineer',           'action'],
        72 => ['medic',              'Medic',              'action'],
        73 => ['bat',                'Bat',                'weapon'],
    ];

    /**
     * @return array{key:string,label:string,category:string}
     */
    public static function resolve(int $mod): array
    {
        $row = self::MAP[$mod] ?? ['unknown', 'Unknown', 'unknown'];

        return [
            'key'      => $row[0],
            'label'    => $row[1],
            'category' => $row[2],
        ];
    }

    /**
     * True if this means-of-death represents a real player frag that should
     * be credited to the killer (i.e. a weapon kill, not world/suicide/action).
     */
    public static function isFrag(int $mod): bool
    {
        return self::resolve($mod)['category'] === 'weapon';
    }

    /**
     * True for self-inflicted / world deaths that count as a death for the
     * victim but never as a frag for anyone.
     */
    public static function isWorldOrSuicide(int $mod): bool
    {
        $cat = self::resolve($mod)['category'];
        return $cat === 'environment' || $cat === 'suicide';
    }

    public static function label(int $mod): string
    {
        return self::resolve($mod)['label'];
    }

    public static function key(int $mod): string
    {
        return self::resolve($mod)['key'];
    }
}
