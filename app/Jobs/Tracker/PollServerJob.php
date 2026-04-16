<?php

namespace App\Jobs\Tracker;

use App\Models\Tracker\TrackerServer;
use App\Services\Tracker\PlayerTrackingService;
use App\Services\Tracker\ServerPollerService;
use App\Services\Tracker\ServerQueryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollServerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 10;

    public function __construct(public int $serverId)
    {}

    public function handle(): void
    {
        $server = TrackerServer::find($this->serverId);
        if (!$server) return;

        $queryService = new ServerQueryService(2, 1);
        $playerTracker = new PlayerTrackingService();
        $poller = new ServerPollerService($queryService, $playerTracker);

        $success = $poller->pollServer($server);

        $interval = match(true) {
            !$success                         => 300,
            $server->current_players >= 15    => 30,
            $server->current_players >= 5     => 60,
            $server->current_players >= 1     => 120,
            default                            => 300,
        };

        $server->refresh();
        $server->update(['next_poll_at' => now()->addSeconds($interval)]);
    }
}
