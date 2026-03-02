<?php

namespace App\Services\Tracker;

use App\Models\Tracker\TrackerGame;
use App\Models\Tracker\TrackerMasterServer;
use Illuminate\Support\Facades\Log;

class MasterServerService
{
    private ServerQueryService $queryService;

    public function __construct(ServerQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    /**
     * Discover servers from all active master servers for a specific game.
     */
    public function discoverServers(TrackerGame $game): array
    {
        $allServers = [];
        $masterServers = $game->masterServers()->where('is_active', true)->get();

        foreach ($masterServers as $master) {
            $master->update(['last_queried_at' => now()]);

            try {
                $servers = $this->queryService->queryMasterServer(
                    $master->address,
                    $master->port,
                    $game->protocol_version
                );

                if (!empty($servers)) {
                    $master->update([
                        'last_success_at' => now(),
                        'servers_found' => count($servers),
                        'failures_count' => 0,
                    ]);

                    foreach ($servers as $s) {
                        $key = $s['ip'] . ':' . $s['port'];
                        $allServers[$key] = $s;
                    }

                    Log::info("Tracker: Master {$master->full_address} returned " . count($servers) . " servers for {$game->short_name}");
                } else {
                    $master->increment('failures_count');
                    Log::warning("Tracker: Master {$master->full_address} returned 0 servers for {$game->short_name}");
                }
            } catch (\Exception $e) {
                $master->increment('failures_count');
                Log::error("Tracker: Master {$master->full_address} error: {$e->getMessage()}");
            }
        }

        return array_values($allServers);
    }

    /**
     * Discover servers for all active games.
     */
    public function discoverAllGames(): array
    {
        $results = [];
        $games = TrackerGame::active()->orderBy('sort_order')->get();

        foreach ($games as $game) {
            $servers = $this->discoverServers($game);
            $results[$game->slug] = [
                'game' => $game->short_name,
                'servers' => $servers,
                'count' => count($servers),
            ];
        }

        return $results;
    }
}
