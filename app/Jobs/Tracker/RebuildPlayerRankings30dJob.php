<?php

namespace App\Jobs\Tracker;

use App\Services\Tracker\Ranking30dService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Event-triggered rebuild of the 30-day PLAYER rankings (ELO).
 *
 * Dispatched (debounced) by MatchLifecycleHandler whenever a match closes,
 * so a player's ELO refreshes within ~20s of a round ending instead of
 * waiting for the 30-minute scheduled rebuild. Only player rankings are
 * recalculated here; server rankings (avg_players) stay on the 30-min job.
 */
class RebuildPlayerRankings30dJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 1;

    public function handle(Ranking30dService $service): void
    {
        $start = microtime(true);
        $count = $service->recalculatePlayers();
        $elapsed = round(microtime(true) - $start, 2);

        Log::info('Event-triggered 30d player ranking rebuild', [
            'players' => $count,
            'seconds' => $elapsed,
        ]);
    }
}
