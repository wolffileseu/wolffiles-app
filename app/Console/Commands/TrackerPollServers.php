<?php
namespace App\Console\Commands;

use App\Jobs\Tracker\PollServerJob;
use App\Models\Tracker\TrackerServer;
use App\Services\Tracker\PlayerTrackingService;
use Illuminate\Console\Command;

class TrackerPollServers extends Command
{
    protected $signature = 'tracker:poll-servers
        {--server= : Specific server ID to poll}';
    protected $description = 'Dispatch poll jobs for all pollable servers due for polling';

    public function handle(): int
    {
        $serverId = $this->option('server');

        if ($serverId) {
            PollServerJob::dispatch((int) $serverId)->onQueue('tracker-high');
            $this->info("Dispatched poll job for server #{$serverId}");
            return 0;
        }

        $servers = TrackerServer::pollable()
            ->where(function ($q) {
                $q->whereNull('next_poll_at')
                  ->orWhere('next_poll_at', '<=', now());
            })
            ->get([
                'id', 'current_players', 'is_online', 'status',
                'last_seen_at', 'first_seen_at',
                'is_enhanced_tracker', 'enhanced_disabled', 'enhanced_last_event_at',
            ]);

        $total = $servers->count();
        $high  = 0;
        $low   = 0;

        foreach ($servers as $server) {
            // High priority: currently online, players on it,
            // OR recent enhanced-tracker events (server IS alive, just query failing)
            $recentEnhanced = $server->is_enhanced_tracker
                && !$server->enhanced_disabled
                && $server->enhanced_last_event_at
                && $server->enhanced_last_event_at->greaterThanOrEqualTo(now()->subMinutes(10));

            if ($server->current_players >= 1 || $server->is_online || $recentEnhanced) {
                PollServerJob::dispatch($server->id)->onQueue('tracker-high');
                $high++;
            } else {
                PollServerJob::dispatch($server->id)->onQueue('tracker-low');
                $low++;
            }
        }

        (new PlayerTrackingService())->endStaleSessions();

        $this->info("Dispatched {$total} jobs (high: {$high}, low: {$low})");
        return 0;
    }
}
