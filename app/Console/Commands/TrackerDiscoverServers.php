<?php
namespace App\Console\Commands;
use App\Models\Tracker\TrackerGame;
use App\Models\Tracker\TrackerServer;
use App\Services\Tracker\GeoIpService;
use App\Services\Tracker\MasterServerService;
use App\Services\Tracker\ServerQueryService;
use Illuminate\Console\Command;
class TrackerDiscoverServers extends Command
{
    protected $signature = 'tracker:discover-servers {--game= : Specific game slug to discover}';
    protected $description = 'Query master servers to discover new game servers';
    public function handle(): int
    {
        $masterService = new MasterServerService(new ServerQueryService());
        $gameSlug = $this->option('game');
        if ($gameSlug) {
            $game = TrackerGame::where('slug', $gameSlug)->first();
            if (!$game) {
                $this->error("Game '{$gameSlug}' not found.");
                return 1;
            }
            $results = [$game->slug => [
                'game' => $game->short_name,
                'servers' => $masterService->discoverServers($game),
                'count' => 0,
            ]];
            $results[$game->slug]['count'] = count($results[$game->slug]['servers']);
        } else {
            $results = $masterService->discoverAllGames();
        }
        // Build spam-IP list: IPs with 5+ servers that were never online
        $spamIps = TrackerServer::whereNull('last_seen_at')
            ->selectRaw('ip, COUNT(*) as cnt')
            ->groupBy('ip')
            ->having('cnt', '>=', 5)
            ->pluck('cnt', 'ip')
            ->keys()
            ->flip()
            ->toArray();
        $totalNew = 0;
        $totalExisting = 0;
        $totalSkipped = 0;
        foreach ($results as $slug => $data) {
            $game = TrackerGame::where('slug', $slug)->first();
            if (!$game) continue;
            $this->info(" {$data['game']}: {$data['count']} servers from master");
            foreach ($data['servers'] as $serverData) {
                // Skip known spam IPs
                if (isset($spamIps[$serverData['ip']])) {
                    $totalSkipped++;
                    continue;
                }
                $existing = TrackerServer::where('ip', $serverData['ip'])
                    ->where('port', $serverData['port'])
                    ->first();
                if ($existing) {
                    if ($existing->status === 'removed') {
                        $existing->update(['status' => 'active']);
                    }
                    $totalExisting++;
                } else {
                    $geo = GeoIpService::lookup($serverData['ip']);
                    TrackerServer::create([
                        'game_id'      => $game->id,
                        'ip'           => $serverData['ip'],
                        'port'         => $serverData['port'],
                        'country'      => $geo['country'] ?? null,
                        'country_code' => $geo['country_code'] ?? null,
                        'city'         => $geo['city'] ?? null,
                        'latitude'     => $geo['latitude'] ?? null,
                        'longitude'    => $geo['longitude'] ?? null,
                        'first_seen_at'=> now(),
                        'status'       => 'pending',
                    ]);
                    $totalNew++;
                }
            }
        }
        $this->info(" Discovery complete: {$totalNew} new, {$totalExisting} existing, {$totalSkipped} skipped (spam IPs)");
        return 0;
    }
}
