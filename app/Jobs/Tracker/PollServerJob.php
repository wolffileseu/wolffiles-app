<?php

namespace App\Jobs\Tracker;

use App\Models\Tracker\TrackerServer;
use App\Services\Tracker\PlayerTrackingService;
use App\Services\Tracker\ServerPollerService;
use App\Services\Tracker\ServerQueryService;
use App\Services\Tracker\EngineVersionParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollServerJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 10;

    public function __construct(public int $serverId)
    {}

    /**
     * Only one queued poll per server at a time. Without this the scheduler
     * (every 30s) re-dispatches servers whose next_poll_at is still stale
     * because their job has not been processed yet, which snowballs.
     */
    public int $uniqueFor = 300;

    public function uniqueId(): string
    {
        return (string) $this->serverId;
    }

    public function handle(): void
    {
        $server = TrackerServer::find($this->serverId);
        if (!$server) return;

        // Admin pause — skip poll, push next_poll_at out so the scheduler
        // stops dispatching. On unpause, the next scheduler tick (or a
        // manual "Poll Now") picks it back up immediately.
        if ($server->polling_paused) {
            $server->update(['next_poll_at' => now()->addHour()]);
            return;
        }

        $queryService = new ServerQueryService(2, 1);
        $playerTracker = new PlayerTrackingService();
        $engineParser = new EngineVersionParser();
        $poller = new ServerPollerService($queryService, $playerTracker, $engineParser);

        $success = $poller->pollServer($server);

        // Single source of truth for next_poll_at. Was previously a local
        // match() block that overrode ServerPollerService::handleOffline()'s
        // next_poll_at, making offlinePollInterval() effectively dead code.
        // Now both online cadence (player-count based) and offline backoff
        // (poll_failures based) live in ServerPollerService. See B-3b.
        $server->refresh();
        $interval = $success
            ? $poller->onlinePollInterval($server)
            : $poller->offlinePollInterval($server);

        $server->update(['next_poll_at' => now()->addSeconds($interval)]);
    }
}
