<?php

namespace App\Console\Commands;

use App\Services\StatisticsService;
use Illuminate\Console\Command;

class UpdateTrendingScores extends Command
{
    protected $signature = 'wolffiles:trending';
    protected $description = 'Recalculate trending scores for all files';

    public function handle(): int
    {
        $count = StatisticsService::calculateTrendingScores();
        $this->info("Updated trending scores for {$count} files.");
        return 0;
    }
}
