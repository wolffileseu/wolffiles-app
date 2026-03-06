<?php
namespace App\Console\Commands;
use App\Models\Tracker\TrackerServer;
use App\Services\Tracker\PlayerTrackingService;
use App\Services\Tracker\ServerPollerService;
use App\Services\Tracker\ServerQueryService;
use Illuminate\Console\Command;

class TrackerPollServers extends Command
{
    protected $signature = 'tracker:poll-servers
        {--server= : Specific server ID to poll}
        {--batch=50 : Number of servers to poll per batch}
        {--timeout=2 : UDP timeout in seconds}';
    protected $description = 'Poll all active servers for current status and players';

    /**
     * Priority polling intervals based on player count:
     * 15+ players  → 30 seconds
     * 5-14 players → 60 seconds
     * 1-4 players  → 120 seconds (2 min)
     * 0 players    → 300 seconds (5 min)
     * offline      → 300 seconds (5 min)
     */
    private function getNextPollInterval(int $playerCount, bool $isOnline): int
    {
        if (!$isOnline) return 300;
        return match(true) {
            $playerCount >= 15 => 30,
            $playerCount >= 5  => 60,
            $playerCount >= 1  => 120,
            default            => 300,
        };
    }

    public function handle(): int
    {
        $serverId = $this->option('server');
        $batchSize = (int) $this->option('batch');
        $timeout = (int) $this->option('timeout');

        $queryService = new ServerQueryService($timeout, 1);
        $playerTracker = new PlayerTrackingService();
        $poller = new ServerPollerService($queryService, $playerTracker);

        if ($serverId) {
            $server = TrackerServer::find($serverId);
            if (!$server) {
                $this->error("Server #{$serverId} not found.");
                return 1;
            }
            $success = $poller->pollServer($server);
            $interval = $this->getNextPollInterval($server->current_players ?? 0, $success);
            $server->update(['next_poll_at' => now()->addSeconds($interval)]);
            $this->info($success
                ? "✓ {$server->full_address} online - {$server->current_players}/{$server->max_players} on {$server->current_map} (next poll in {$interval}s)"
                : "✗ {$server->full_address} offline (next poll in {$interval}s)"
            );
            return 0;
        }

        // Only poll servers where next_poll_at is due (or never polled)
        $servers = TrackerServer::active()
            ->where(function ($q) {
                $q->whereNull('next_poll_at')
                  ->orWhere('next_poll_at', '<=', now());
            })
            ->get();

        $total = $servers->count();
        $this->info("Polling {$total} servers due for poll...");

        $results = ['polled' => 0, 'online' => 0, 'offline' => 0, 'errors' => 0];
        $startTime = microtime(true);

        foreach ($servers->chunk($batchSize) as $batch) {
            foreach ($batch as $server) {
                try {
                    $success = $poller->pollServer($server);
                    $results['polled']++;
                    $success ? $results['online']++ : $results['offline']++;

                    // Set next poll time based on current player count
                    $interval = $this->getNextPollInterval($server->current_players ?? 0, $success);
                    $server->update(['next_poll_at' => now()->addSeconds($interval)]);

                } catch (\Exception $e) {
                    $results['errors']++;
                    // On error, retry in 2 minutes
                    $server->update(['next_poll_at' => now()->addMinutes(2)]);
                }
            }
        }

        $playerTracker->endStaleSessions();

        $elapsed = round(microtime(true) - $startTime, 1);
        $this->info("✓ Polled: {$results['polled']} | Online: {$results['online']} | Offline: {$results['offline']} | Errors: {$results['errors']} | Time: {$elapsed}s");
        return 0;
    }
}
