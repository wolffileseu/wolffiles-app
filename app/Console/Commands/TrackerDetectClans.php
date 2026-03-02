<?php

namespace App\Console\Commands;

use App\Services\Tracker\ClanDetectionService;
use Illuminate\Console\Command;

class TrackerDetectClans extends Command
{
    protected $signature = 'tracker:detect-clans';
    protected $description = 'Detect clan tags from player names and update memberships';

    public function handle(ClanDetectionService $service): int
    {
        $this->info('Detecting clans...');
        $stats = $service->processAllPlayers();
        $this->info("Processed {$stats['processed']} players, created {$stats['clans_created']} clans, added {$stats['members_added']} members");
        return self::SUCCESS;
    }
}
