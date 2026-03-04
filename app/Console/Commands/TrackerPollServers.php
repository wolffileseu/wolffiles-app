<?php
namespace App\Console\Commands;
use App\Models\Tracker\TrackerServer;
use App\Services\Tracker\ColorCodeService;
use App\Services\Tracker\PlayerTrackingService;
use App\Services\Tracker\ServerPollerService;
use App\Services\Tracker\ServerQueryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
class TrackerPollServers extends Command
{
    protected $signature = 'tracker:poll-servers
        {--server= : Specific server ID to poll}
        {--batch=50 : Number of servers to poll per batch}
        {--timeout=2 : UDP timeout in seconds}';
    protected $description = 'Poll all active servers for current status and players';
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
            $this->info($success
                ? " {$server->full_address} online - {$server->current_players}/{$server->max_players} on {$server->current_map}"
                : " {$server->full_address} offline"
            );
            return 0;
        }
        $servers = TrackerServer::active()
            ->where(function ($q) {
                $q->where('is_online', true)
                   ->orWhereNull('last_poll_at')
                   ->orWhere('last_poll_at', '<', now()->subMinutes(10));
            })
            ->get();
        $total = $servers->count();
        $this->info("Polling {$total} servers in batches of {$batchSize}...");
        $results = ['polled' => 0, 'online' => 0, 'offline' => 0, 'errors' => 0];
        $startTime = microtime(true);
        foreach ($servers->chunk($batchSize) as $batch) {
            foreach ($batch as $server) {
                try {
                    $success = $poller->pollServer($server);
                    $results['polled']++;
                    if ($success) {
                        $results['online']++;
                    } else {
                        $results['offline']++;
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                }
            }
        }
        $playerTracker->endStaleSessions();
        $elapsed = round(microtime(true) - $startTime, 1);
        Cache::put('tracker:last_poll_at', now(), 600);
        $this->info(" Polled: {$results['polled']} | Online: {$results['online']} | Offline: {$results['offline']} | Errors: {$results['errors']} | Time: {$elapsed}s");
        return 0;
    }
}
