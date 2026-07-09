<?php

namespace App\Console\Commands;

use App\Models\Tracker\TrackerMap;
use App\Services\Tracker\MapLinkService;
use Illuminate\Console\Command;

class TrackerSyncMaps extends Command
{
    protected $signature = 'tracker:sync-maps';
    protected $description = 'Sync server maps with Wolffiles downloads';

    public function handle(): int
    {
        $count = MapLinkService::syncMaps();
        $linked = TrackerMap::whereNotNull('file_id')->count();
        $this->info("✅ {$count} maps synced, {$linked} linked to files");
        return 0;
    }
}
