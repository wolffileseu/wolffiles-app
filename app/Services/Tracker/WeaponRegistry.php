<?php

namespace App\Services\Tracker;

/**
 * Central weapon metadata registry.
 *
 * Maps ETL weapon bit indices (from extWeaponStats_e in bg_public.h) to
 * display names, slugs, icon paths, categories, and flavor text.
 *
 * The bit index is what appears in ws-packets and in the weapon_bit column
 * of tracker_match_player_weapon_stats / tracker_player_weapon_stats.
 */
class WeaponRegistry
{
    /**
     * @var array<int, array{name:string,slug:string,icon:string,category:string,side:string,lore:string}>
     */
    private const WEAPONS = [
        0 => [
            'name' => 'Knife', 'slug' => 'knife', 'icon' => 'iconw_knife.svg',
            'category' => 'melee', 'side' => 'axis',
            'lore' => 'The silent finisher. One-hit kill from behind, but you have to get there first.',
        ],
        1 => [
            'name' => 'K-Bar Knife', 'slug' => 'kbar', 'icon' => 'iconw_knife_kbar.svg',
            'category' => 'melee', 'side' => 'allied',
            'lore' => 'Standard issue combat knife for the Allied forces. As brutal as it is quiet.',
        ],
        2 => [
            'name' => 'Luger P08', 'slug' => 'luger', 'icon' => 'iconw_luger.svg',
            'category' => 'pistol', 'side' => 'axis',
            'lore' => 'German officer sidearm. Elegant, accurate, and deadly at close range.',
        ],
        3 => [
            'name' => 'Colt .45', 'slug' => 'colt', 'icon' => 'iconw_colt.svg',
            'category' => 'pistol', 'side' => 'allied',
            'lore' => 'M1911 in trained hands will stop anything within twenty paces.',
        ],
        4 => [
            'name' => 'MP40', 'slug' => 'mp40', 'icon' => 'iconw_mp40.svg',
            'category' => 'smg', 'side' => 'axis',
            'lore' => 'Maschinenpistole 40. The sound of Axis infantry. High fire rate, manageable recoil.',
        ],
        5 => [
            'name' => 'Thompson', 'slug' => 'thompson', 'icon' => 'iconw_thompson.svg',
            'category' => 'smg', 'side' => 'allied',
            'lore' => 'The "Tommy Gun". Heavier than the MP40, hits harder at close range.',
        ],
        6 => [
            'name' => 'Sten', 'slug' => 'sten', 'icon' => 'iconw_sten.svg',
            'category' => 'smg', 'side' => 'allied',
            'lore' => 'British SMG with a silencer. Stealth over stopping power.',
        ],
        7 => [
            'name' => 'FG42', 'slug' => 'fg42', 'icon' => 'iconw_fg42.svg',
            'category' => 'rifle', 'side' => 'axis',
            'lore' => 'Fallschirmjägergewehr 42. Paratrooper rifle. Scoped, deadly, rare.',
        ],
        8 => [
            'name' => 'Panzerfaust', 'slug' => 'panzerfaust', 'icon' => 'iconw_panzerfaust.svg',
            'category' => 'explosive', 'side' => 'axis',
            'lore' => 'One shot. One corpse. One embarrassed tank crew.',
        ],
        9 => [
            'name' => 'Bazooka', 'slug' => 'bazooka', 'icon' => 'iconw_bazooka.svg',
            'category' => 'explosive', 'side' => 'allied',
            'lore' => 'Allied answer to the Panzerfaust. Slower reload, same devastation.',
        ],
        10 => [
            'name' => 'Flamethrower', 'slug' => 'flamethrower', 'icon' => 'iconw_flamethrower.svg',
            'category' => 'special', 'side' => 'both',
            'lore' => 'For when you want them to scream before they die.',
        ],
        11 => [
            'name' => 'Grenade', 'slug' => 'grenade', 'icon' => 'iconw_grenade.svg',
            'category' => 'explosive', 'side' => 'both',
            'lore' => 'Cook it, throw it, duck. The great equalizer.',
        ],
        12 => [
            'name' => 'Mortar (Allied)', 'slug' => 'mortar-allied', 'icon' => 'iconw_mortar.svg',
            'category' => 'explosive', 'side' => 'allied',
            'lore' => 'Sit back, lob shells, ruin campers. The artillery of impatient engineers.',
        ],
        13 => [
            'name' => 'Mortar (Axis)', 'slug' => 'mortar-axis', 'icon' => 'iconw_mortar_ax.svg',
            'category' => 'explosive', 'side' => 'axis',
            'lore' => 'Indirect fire from safe distance. The enemy sees only the whistle.',
        ],
        14 => [
            'name' => 'Dynamite', 'slug' => 'dynamite', 'icon' => 'iconw_dynamite.svg',
            'category' => 'explosive', 'side' => 'both',
            'lore' => 'Plant. Arm. Defend. The engineer\'s love language.',
        ],
        15 => [
            'name' => 'Airstrike', 'slug' => 'airstrike', 'icon' => 'iconw_smokegrenade.svg',
            'category' => 'special', 'side' => 'both',
            'lore' => 'Yellow smoke means move. Red smoke means run. Either way, they\'re already falling.',
        ],
        16 => [
            'name' => 'Artillery', 'slug' => 'artillery', 'icon' => 'iconw_binoculars.svg',
            'category' => 'special', 'side' => 'both',
            'lore' => 'Point. Call. Wait. The Field Op\'s gift from the gods.',
        ],
        17 => [
            'name' => 'Satchel', 'slug' => 'satchel', 'icon' => 'iconw_satchel.svg',
            'category' => 'explosive', 'side' => 'both',
            'lore' => 'Remote-detonated demolition. Covert Ops classic.',
        ],
        18 => [
            'name' => 'Rifle Grenade', 'slug' => 'rifle-grenade', 'icon' => 'iconw_m1_garand_gren.svg',
            'category' => 'explosive', 'side' => 'both',
            'lore' => 'Turn your rifle into a mortar. Takes patience and forgiveness of recoil.',
        ],
        19 => [
            'name' => 'Landmine', 'slug' => 'landmine', 'icon' => 'iconw_landmine.svg',
            'category' => 'explosive', 'side' => 'both',
            'lore' => 'Plant and forget. Check back later for parts.',
        ],
        20 => [
            'name' => 'MG42', 'slug' => 'mg42', 'icon' => 'iconw_mg42.svg',
            'category' => 'heavy', 'side' => 'axis',
            'lore' => 'The "Hitler\'s Buzzsaw". Twelve-hundred rounds per minute of pure denial.',
        ],
        21 => [
            'name' => 'Browning', 'slug' => 'browning', 'icon' => 'iconw_browning.svg',
            'category' => 'heavy', 'side' => 'allied',
            'lore' => 'Allied .30 cal. Heavier, slower, equally terrifying.',
        ],
        22 => [
            'name' => 'Carbine', 'slug' => 'carbine', 'icon' => 'iconw_m1_garand.svg',
            'category' => 'rifle', 'side' => 'allied',
            'lore' => 'M1 Carbine. Quick, light, underestimated.',
        ],
        23 => [
            'name' => 'Kar98', 'slug' => 'kar98', 'icon' => 'iconw_kar98.svg',
            'category' => 'sniper', 'side' => 'axis',
            'lore' => 'Karabiner 98k. One shot, one kill, if you can find me.',
        ],
        24 => [
            'name' => 'Garand', 'slug' => 'garand', 'icon' => 'iconw_m1_garand.svg',
            'category' => 'sniper', 'side' => 'allied',
            'lore' => 'The iconic "ping" when the clip ejects. Last round is a warning.',
        ],
        25 => [
            'name' => 'K43', 'slug' => 'k43', 'icon' => 'iconw_mauser.svg',
            'category' => 'sniper', 'side' => 'axis',
            'lore' => 'Gewehr 43. Semi-auto scoped rifle. Built for patient killers.',
        ],
        26 => [
            'name' => 'MP34', 'slug' => 'mp34', 'icon' => 'iconw_mp34.svg',
            'category' => 'smg', 'side' => 'axis',
            'lore' => 'Steyr-Solothurn. Austrian precision meets Axis intent.',
        ],
        27 => [
            'name' => 'Syringe', 'slug' => 'syringe', 'icon' => 'iconw_syringe.svg',
            'category' => 'support', 'side' => 'both',
            'lore' => 'Revive friendlies. Kill enemies. The medic paradox.',
        ],
    ];

    private const CATEGORY_LABELS = [
        'melee'     => 'Melee',
        'pistol'    => 'Pistols',
        'smg'       => 'Submachine Guns',
        'rifle'     => 'Rifles',
        'sniper'    => 'Sniper Rifles',
        'heavy'     => 'Heavy Weapons',
        'explosive' => 'Explosives',
        'special'   => 'Special',
        'support'   => 'Support',
    ];

    public static function all(): array
    {
        return self::WEAPONS;
    }

    public static function get(int $bit): ?array
    {
        return self::WEAPONS[$bit] ?? null;
    }

    public static function findBySlug(string $slug): ?array
    {
        foreach (self::WEAPONS as $bit => $w) {
            if ($w['slug'] === $slug) {
                return ['bit' => $bit] + $w;
            }
        }
        return null;
    }

    public static function iconUrl(int $bit): string
    {
        $w = self::get($bit);
        return $w ? asset('img/tracker/weapons/' . $w['icon']) : asset('img/tracker/weapons/iconw_grenade.svg');
    }

    public static function name(int $bit): string
    {
        return self::get($bit)['name'] ?? "Weapon #{$bit}";
    }

    public static function slug(int $bit): string
    {
        return self::get($bit)['slug'] ?? "weapon-{$bit}";
    }

    public static function categoryLabel(string $category): string
    {
        return self::CATEGORY_LABELS[$category] ?? ucfirst($category);
    }

    public static function groupedByCategory(): array
    {
        $groups = [];
        foreach (self::WEAPONS as $bit => $w) {
            $groups[$w['category']][$bit] = $w;
        }
        // Stable ordering
        $order = array_keys(self::CATEGORY_LABELS);
        uksort($groups, fn($a, $b) => array_search($a, $order) <=> array_search($b, $order));
        return $groups;
    }
}
