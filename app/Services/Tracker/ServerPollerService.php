<?php

namespace App\Services\Tracker;

use App\Models\Tracker\TrackerServer;
use App\Models\Tracker\TrackerServerHistory;
use App\Models\Tracker\TrackerServerSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServerPollerService
{
    private ServerQueryService $queryService;
    private PlayerTrackingService $playerTracker;

    public function __construct(ServerQueryService $queryService, PlayerTrackingService $playerTracker)
    {
        $this->queryService = $queryService;
        $this->playerTracker = $playerTracker;
    }

    /**
     * Poll all active servers.
     */
    public function pollAll(): array
    {
        $servers = TrackerServer::active()->get();
        $results = ['polled' => 0, 'online' => 0, 'offline' => 0, 'errors' => 0];

        foreach ($servers as $server) {
            try {
                $success = $this->pollServer($server);
                $results['polled']++;

                if ($success) {
                    $results['online']++;
                } else {
                    $results['offline']++;
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error("Tracker: Error polling {$server->full_address}: {$e->getMessage()}");
            }
        }

        // End stale sessions
        $this->playerTracker->endStaleSessions();

        return $results;
    }

    /**
     * Poll a single server.
     */
    public function pollServer(TrackerServer $server): bool
    {
        $data = $this->queryService->queryServer($server->ip, $server->port);

        if ($data === null) {
            $this->handleOffline($server);
            return false;
        }

        $this->updateServerFromResponse($server, $data);
        $this->saveSnapshot($server);

        // Track players
        if (!empty($data['players'])) {
            $this->playerTracker->processPlayerList($server, $data['players'], $data['settings']);
        }

        return true;
    }

    /**
     * Update server record from query response.
     */
    private function updateServerFromResponse(TrackerServer $server, array $data): void
    {
        $settings = $data['settings'] ?? [];
        $players = $data['players'] ?? [];

        $hostname = $settings['sv_hostname'] ?? $settings['hostname'] ?? $server->hostname;
        $map = $settings['mapname'] ?? $server->current_map;
        $maxPlayers = (int)($settings['sv_maxclients'] ?? $settings['maxclients'] ?? $server->max_players);
        $gametype = $settings['g_gametype'] ?? $settings['gametype'] ?? $server->gametype;
        $modName = $settings['gamename'] ?? $settings['fs_game'] ?? $server->mod_name;
        $modVersion = $settings['gameversion'] ?? $settings['version'] ?? $server->mod_version;
        $password = (bool)($settings['g_needpass'] ?? $settings['needpass'] ?? 0);
        $pure = isset($settings['sv_pure']) ? (bool)$settings['sv_pure'] : $server->sv_pure;
        $punkbuster = isset($settings['sv_punkbuster']) ? (bool)$settings['sv_punkbuster'] : $server->punkbuster;
        $os = $settings['sv_os'] ?? $settings['sys_cpustring'] ?? $server->os;

        // Auto-detect game type for protocol 84 (ET 2.60b vs ETL)
        if ($server->game && (int)$server->game->protocol_version === 84) {
            $versionStr = $modVersion . ' ' . ($settings['version'] ?? '') . ' ' . ($settings['gameversion'] ?? '');
            $isETL = stripos($versionStr, 'ET Legacy') !== false
                  || stripos($versionStr, 'etlegacy') !== false
                  || stripos($modName ?? '', 'legacy') !== false;
            $correctGameId = $isETL ? 5 : 4;
            if ($server->game_id !== $correctGameId) {
                $server->game_id = $correctGameId;
            }
        }

        // Filter out bots (ping 0 is usually a bot)
        $realPlayers = array_filter($players, fn($p) => ($p['ping'] ?? 0) > 0);

        $server->update([
            'hostname' => $hostname,
            'hostname_clean' => ColorCodeService::toClean($hostname),
            'hostname_html' => ColorCodeService::toHtml($hostname),
            'current_map' => $map,
            'current_players' => count($realPlayers),
            'max_players' => $maxPlayers,
            'gametype' => $gametype,
            'mod_name' => $modName,
            'mod_version' => $modVersion,
            'game_id' => $server->game_id,
            'needs_password' => $password,
            'sv_pure' => $pure,
            'punkbuster' => $punkbuster,
            'os' => $os,
            'is_online' => true,
            'last_seen_at' => now(),
            'last_poll_at' => now(),
            'poll_failures' => 0,
            'status' => 'active',
        ]);

        // Update server settings (key-value store)
        $this->updateServerSettings($server, $settings);

        // Update map stats
        $this->updateMapStats($server, $map, count($realPlayers));
    }

    /**
     * Store all server CVARs.
     */
    private function updateServerSettings(TrackerServer $server, array $settings): void
    {
        foreach ($settings as $key => $value) {
            TrackerServerSetting::updateOrCreate(
                ['server_id' => $server->id, 'key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }
    }

    /**
     * Update map statistics for this server.
     */
    private function updateMapStats(TrackerServer $server, ?string $map, int $playerCount): void
    {
        if (empty($map)) return;

        $stat = $server->mapStats()->firstOrCreate(
            ['map_name' => $map],
            ['times_played' => 0, 'total_time_minutes' => 0, 'peak_players' => 0]
        );

        // Check if map changed since last poll
        $lastHistory = $server->history()->latest('polled_at')->first();
        if (!$lastHistory || $lastHistory->map !== $map) {
            $stat->increment('times_played');
        }

        // Add ~2 minutes (poll interval) to total time
        $stat->increment('total_time_minutes', 2);
        $stat->update([
            'last_played_at' => now(),
            'avg_players' => DB::raw("(avg_players * (total_time_minutes - 2) + " . (int) $playerCount . " * 2) / total_time_minutes"),
            'peak_players' => max($stat->peak_players, (int) $playerCount),
        ]);
    }

    /**
     * Save a history snapshot.
     */
    private function saveSnapshot(TrackerServer $server): void
    {
        TrackerServerHistory::create([
            'server_id' => $server->id,
            'map' => $server->current_map,
            'players' => $server->current_players,
            'max_players' => $server->max_players,
            'gametype' => $server->gametype,
            'polled_at' => now(),
        ]);
    }

    /**
     * Handle an offline/unreachable server.
     */
    private function handleOffline(TrackerServer $server): void
    {
        $server->increment('poll_failures');

        // Mark offline after 3 consecutive failures
        if ($server->poll_failures >= 3) {
            $server->update([
                'is_online' => false,
                'current_players' => 0,
                'last_poll_at' => now(),
            ]);

            // End all active sessions on this server
            $this->playerTracker->endServerSessions($server);
        } else {
            $server->update(['last_poll_at' => now()]);
        }
    }
}
