<?php

namespace App\Console\Commands;

use App\Services\Tracker\Ranking30dService;
use Illuminate\Console\Command;

/**
 * Rebuild 30-day server and player rankings.
 *
 * Replaces the legacy tracker:rebuild-rankings command (which only filled avg_players_30d).
 * Fills both tracker_server_rankings (all columns) and tracker_player_rankings_30d.
 */
class RebuildRankings30d extends Command
{
    protected $signature = 'tracker:rebuild-rankings-30d
                            {--servers-only : Only rebuild server rankings}
                            {--players-only : Only rebuild player rankings}';

    protected $description = 'Rebuild 30-day server and player rankings with all metrics';

    public function handle(Ranking30dService $service): int
    {
        $startTotal = microtime(true);

        if (! $this->option('players-only')) {
            $t = microtime(true);
            $this->info('→ Rebuilding server rankings...');
            $count = $service->recalculateServers();
            $elapsed = round(microtime(true) - $t, 2);
            $this->info("  {$count} servers ranked ({$elapsed}s)");
        }

        if (! $this->option('servers-only')) {
            $t = microtime(true);
            $this->info('→ Rebuilding player rankings...');
            $count = $service->recalculatePlayers();
            $elapsed = round(microtime(true) - $t, 2);
            $this->info("  {$count} players ranked ({$elapsed}s)");
        }

        $total = round(microtime(true) - $startTotal, 2);
        $this->info("Done in {$total}s");

        return self::SUCCESS;
    }
}
