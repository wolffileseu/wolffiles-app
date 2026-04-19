<?php

namespace App\Console\Commands;

use App\Services\Tracker\EloService;
use Illuminate\Console\Command;

/**
 * Bulk-recompute ELO for all eligible Poller-tracked players.
 *
 * Run manually:
 *   php artisan tracker:calculate-elo
 *
 * Scheduled daily in routes/console.php — see scheduler setup.
 */
class TrackerCalculateElo extends Command
{
    protected $signature = 'tracker:calculate-elo';

    protected $description = 'Recompute Classic Percentile ELO for all eligible players';

    public function handle(EloService $service): int
    {
        $this->info('Calculating ELO for eligible players...');
        $start = microtime(true);

        $count = $service->calculateForAll();

        $elapsed = round(microtime(true) - $start, 2);
        $this->info("Updated {$count} players in {$elapsed}s");

        return self::SUCCESS;
    }
}
