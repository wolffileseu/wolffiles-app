<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class DemoCategorySeeder extends Seeder
{
    public function run(): void
    {
        $et = Category::firstOrCreate(['slug' => 'demos-enemy-territory'], [
            'name' => 'Enemy Territory', 'type' => 'demo', 'icon' => '🎮',
            'sort_order' => 1, 'is_active' => true,
            'name_translations' => ['en' => 'Enemy Territory', 'de' => 'Enemy Territory'],
            'description' => 'Wolfenstein: Enemy Territory demo files',
        ]);

        $etSubs = [
            ['name' => 'Clanwars', 'slug' => 'demos-et-clanwars', 'sort_order' => 1, 'description' => 'Official clan match demos'],
            ['name' => 'Officials / Cups', 'slug' => 'demos-et-officials', 'sort_order' => 2, 'description' => 'ESL, ClanBase, ETF2L cup and league demos'],
            ['name' => 'Public', 'slug' => 'demos-et-public', 'sort_order' => 3, 'description' => 'Public server gameplay demos'],
            ['name' => 'Trickjump', 'slug' => 'demos-et-trickjump', 'sort_order' => 4, 'description' => 'Trickjump and movement demos'],
            ['name' => 'Anticheat', 'slug' => 'demos-et-anticheat', 'sort_order' => 5, 'description' => 'Anticheat evidence and review demos'],
            ['name' => 'Fragmovie Material', 'slug' => 'demos-et-fragmovie', 'sort_order' => 6, 'description' => 'Raw footage demos for fragmovie editing'],
            ['name' => 'ETTV', 'slug' => 'demos-et-ettv', 'sort_order' => 7, 'description' => 'ETTV recorded demos (tv_84 format)'],
            ['name' => 'LAN Events', 'slug' => 'demos-et-lan', 'sort_order' => 8, 'description' => 'LAN event demos (Crossfire Challenge, AEF, etc.)'],
        ];

        foreach ($etSubs as $sub) {
            Category::firstOrCreate(['slug' => $sub['slug']], array_merge($sub, [
                'parent_id' => $et->id, 'type' => 'demo', 'is_active' => true,
                'name_translations' => ['en' => $sub['name'], 'de' => $sub['name']],
            ]));
        }

        $rtcw = Category::firstOrCreate(['slug' => 'demos-rtcw'], [
            'name' => 'Return to Castle Wolfenstein', 'type' => 'demo', 'icon' => '🏰',
            'sort_order' => 2, 'is_active' => true,
            'name_translations' => ['en' => 'Return to Castle Wolfenstein', 'de' => 'Return to Castle Wolfenstein'],
            'description' => 'RtCW multiplayer demo files',
        ]);

        $rtcwSubs = [
            ['name' => 'Clanwars', 'slug' => 'demos-rtcw-clanwars', 'sort_order' => 1, 'description' => 'RtCW clan match demos'],
            ['name' => 'Public', 'slug' => 'demos-rtcw-public', 'sort_order' => 2, 'description' => 'Public gameplay demos'],
            ['name' => 'Trickjump', 'slug' => 'demos-rtcw-trickjump', 'sort_order' => 3, 'description' => 'RtCW trickjump demos'],
        ];

        foreach ($rtcwSubs as $sub) {
            Category::firstOrCreate(['slug' => $sub['slug']], array_merge($sub, [
                'parent_id' => $rtcw->id, 'type' => 'demo', 'is_active' => true,
                'name_translations' => ['en' => $sub['name'], 'de' => $sub['name']],
            ]));
        }

        Category::firstOrCreate(['slug' => 'demos-quake3'], [
            'name' => 'Quake 3 Arena', 'type' => 'demo', 'icon' => '⚡',
            'sort_order' => 3, 'is_active' => true,
            'name_translations' => ['en' => 'Quake 3 Arena', 'de' => 'Quake 3 Arena'],
        ]);

        Category::firstOrCreate(['slug' => 'demos-etqw'], [
            'name' => 'ET: Quake Wars', 'type' => 'demo', 'icon' => '🚀',
            'sort_order' => 4, 'is_active' => true,
            'name_translations' => ['en' => 'ET: Quake Wars', 'de' => 'ET: Quake Wars'],
        ]);
    }
}
