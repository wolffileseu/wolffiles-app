<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrackerGameSeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            [
                'name' => 'Wolfenstein: Enemy Territory 2.55',
                'slug' => 'et-255',
                'short_name' => 'ET 2.55',
                'protocol_version' => 82,
                'default_port' => 27960,
                'color' => '#CC6600',
                'sort_order' => 1,
            ],
            [
                'name' => 'Wolfenstein: Enemy Territory 2.56',
                'slug' => 'et-256',
                'short_name' => 'ET 2.56',
                'protocol_version' => 83,
                'default_port' => 27960,
                'color' => '#DD7700',
                'sort_order' => 2,
            ],
            [
                'name' => 'Wolfenstein: Enemy Territory 2.60',
                'slug' => 'et-260',
                'short_name' => 'ET 2.60',
                'protocol_version' => 84,
                'default_port' => 27960,
                'color' => '#EE8800',
                'sort_order' => 3,
            ],
            [
                'name' => 'Wolfenstein: Enemy Territory 2.60b',
                'slug' => 'et-260b',
                'short_name' => 'ET 2.60b',
                'protocol_version' => 84,
                'default_port' => 27960,
                'color' => '#FF9900',
                'sort_order' => 4,
            ],
            [
                'name' => 'ET: Legacy',
                'slug' => 'etlegacy',
                'short_name' => 'ETL',
                'protocol_version' => 84,
                'default_port' => 27960,
                'color' => '#4CAF50',
                'sort_order' => 5,
            ],
            [
                'name' => 'Return to Castle Wolfenstein',
                'slug' => 'rtcw',
                'short_name' => 'RtCW',
                'protocol_version' => 57,
                'default_port' => 27960,
                'color' => '#2196F3',
                'sort_order' => 6,
            ],
            [
                'name' => 'RtCW Cooperative',
                'slug' => 'rtcw-coop',
                'short_name' => 'RtCW Coop',
                'protocol_version' => 57,
                'default_port' => 27960,
                'color' => '#9C27B0',
                'sort_order' => 7,
            ],
        ];

        foreach ($games as $game) {
            DB::table('tracker_games')->updateOrInsert(
                ['slug' => $game['slug']],
                array_merge($game, [
                    'query_type' => 'quake3',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Master Servers
        $masterServers = [
            ['slug' => 'et-255', 'address' => 'master.idsoftware.com', 'port' => 27950],
            ['slug' => 'et-256', 'address' => 'master.idsoftware.com', 'port' => 27950],
            ['slug' => 'et-260', 'address' => 'master.idsoftware.com', 'port' => 27950],
            ['slug' => 'et-260b', 'address' => 'master.idsoftware.com', 'port' => 27950],
            ['slug' => 'et-260b', 'address' => 'master0.etmaster.net', 'port' => 27950],
            ['slug' => 'etlegacy', 'address' => 'master.etlegacy.com', 'port' => 27950],
            ['slug' => 'rtcw', 'address' => 'wolfmaster.idsoftware.com', 'port' => 27950],
        ];

        foreach ($masterServers as $ms) {
            $gameId = DB::table('tracker_games')->where('slug', $ms['slug'])->value('id');
            if ($gameId) {
                DB::table('tracker_master_servers')->updateOrInsert(
                    ['game_id' => $gameId, 'address' => $ms['address'], 'port' => $ms['port']],
                    [
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('Seeded ' . count($games) . ' games and ' . count($masterServers) . ' master servers.');
    }
}
