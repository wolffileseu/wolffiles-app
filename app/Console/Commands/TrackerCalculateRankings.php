<?php

namespace App\Console\Commands;

use App\Services\Tracker\RankingService;
use Illuminate\Console\Command;

class TrackerCalculateRankings extends Command
{
    protected $signature = 'tracker:calculate-rankings {--period=daily}';
    protected $description = 'Calculate player rankings for a given period';

    public function handle(RankingService $service): int
    {
        $period = $this->option('period');
        $this->info("Calculating {$period} rankings...");
        $count = $service->calculateRankings($period);
        $this->info("Ranked {$count} players");
        return self::SUCCESS;
    }
}
