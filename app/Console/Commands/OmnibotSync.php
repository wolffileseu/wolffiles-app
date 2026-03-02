<?php

namespace App\Console\Commands;

use App\Services\OmnibotWaypointService;
use Illuminate\Console\Command;

class OmnibotSync extends Command
{
    protected $signature = 'omnibot:sync
        {--scan-all : Scan all existing files for waypoints}
        {--pull : Pull latest from GitHub}
        {--rebuild : Rebuild indexes only}';

    protected $description = 'Sync Omni-Bot waypoints';

    public function handle(): int
    {
        $service = app(OmnibotWaypointService::class);

        if ($this->option('pull')) {
            $this->info('Pulling from GitHub...');
            $result = $service->pullFromGitHub();
            $this->info("New ET files: {$result['new_et']}");
            $this->info("New RTCW files: {$result['new_rtcw']}");
        }

        if ($this->option('scan-all')) {
            $this->info('Scanning all files...');
            $result = $service->scanAll();
            $this->info('New: ' . count($result['new']));
            $this->info('Existing: ' . count($result['existing']));
        }

        $this->info('Rebuilding indexes...');
        $service->rebuildIndex('et');
        $service->rebuildIndex('rtcw');

        $etIdx = json_decode(file_get_contents(storage_path('app/omnibot/waypoint-index.json')), true);
        $rtcwIdx = json_decode(file_get_contents(storage_path('app/omnibot/rtcw-waypoint-index.json')), true);
        $this->info('ET maps: ' . count($etIdx['maps']));
        $this->info('RTCW maps: ' . count($rtcwIdx['maps']));
        $this->info('Done!');
        return 0;
    }
}
