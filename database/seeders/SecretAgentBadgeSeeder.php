<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class SecretAgentBadgeSeeder extends Seeder
{
    public function run(): void
    {
        Badge::updateOrCreate(
            ['slug' => 'secret-agent'],
            [
                'name' => 'Secret Agent',
                'slug' => 'secret-agent',
                'icon' => '🎮',
                'description' => 'Hat das Wolffiles.eu Konami Code Easter Egg entdeckt und Wolfenstein 3D gemeistert!',
            ]
        );
    }
}
