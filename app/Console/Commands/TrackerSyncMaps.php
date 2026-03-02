<?php

namespace App\Console\Commands;

use App\Services\Tracker\MapLinkService;
use Illuminate\Console\Command;

class TrackerSyncMaps extends Command
{
    protected $signature = 'tracker:sync-maps';
    protected $description = 'Sync server maps with Wolffiles downloads';

    public function handle(): int
    {
        $count = MapLinkService::syncMaps();
        $linked = \App\Models\Tracker\TrackerMap::whereNotNull('file_id')->count();
        $this->info("✅ {$count} maps synced, {$linked} linked to files");
        return 0;
    }
}
