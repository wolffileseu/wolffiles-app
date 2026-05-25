<?php

namespace Database\Seeders;

use App\Models\BugTracker\Project;
use Illuminate\Database\Seeder;

class BugTrackerProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = [
            [
                'slug' => 'wolffiles',
                'name' => 'Wolffiles Platform',
                'description' => 'Main community archive and tooling platform (wolffiles.eu).',
                'color' => '#e74c3c',
                'icon'  => '🐺',
                'github_repo' => 'wolffileseu/wolffiles-app',
                'sort_order' => 10,
            ],
            [
                'slug' => 'etds',
                'name' => 'ETDS',
                'description' => 'Enemy Territory Dedicated Server fork.',
                'color' => '#f39c12',
                'icon'  => '⚔️',
                'github_repo' => 'wolffileseu/etds',
                'sort_order' => 20,
            ],
            [
                'slug' => 'uploader',
                'name' => 'Wolffiles Uploader',
                'description' => 'WinUI 3 desktop upload client.',
                'color' => '#3498db',
                'icon'  => '📤',
                'github_repo' => 'wolffileseu/wolffiles-uploader',
                'sort_order' => 30,
            ],
            [
                'slug' => 'omnibot',
                'name' => 'Omnibot Waypoints',
                'description' => 'Omnibot waypoint repository and sync service.',
                'color' => '#9b59b6',
                'icon'  => '🤖',
                'github_repo' => 'wolffileseu/omnibot-waypoints',
                'sort_order' => 40,
            ],
            [
                'slug' => 'bot',
                'name' => 'Wolffiles Discord Bot',
                'description' => 'Node.js Discord bot for the community.',
                'color' => '#5865f2',
                'icon'  => '💬',
                'github_repo' => 'wolffileseu/wolffiles-bot',
                'sort_order' => 50,
            ],
        ];

        foreach ($projects as $data) {
            Project::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
