<?php

namespace App\Services\Tracker;

use App\Models\Tracker\TrackerServer;
use App\Services\Tracker\GeoIpService;
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

        $hostname = $this->toUtf8((string) ($settings['sv_hostname'] ?? $settings['hostname'] ?? $server->hostname ?? ''));
        $map = $this->toUtf8((string) ($settings['mapname'] ?? $server->current_map ?? ''));
        $maxPlayers = (int)($settings['sv_maxclients'] ?? $settings['maxclients'] ?? $server->max_players);
        $gametype = $settings['g_gametype'] ?? $settings['gametype'] ?? $server->gametype;
        $modName = $settings['gamename'] ?? $settings['fs_game'] ?? $server->mod_name;
        $modVersion = $settings['gameversion'] ?? $settings['version'] ?? $server->mod_version;
        $password = (bool)($settings['g_needpass'] ?? $settings['needpass'] ?? 0);
        $pure = isset($settings['sv_pure']) ? (bool)$settings['sv_pure'] : $server->sv_pure;
        $punkbuster = isset($settings['sv_punkbuster']) ? (bool)$settings['sv_punkbuster'] : $server->punkbuster;
        $os = $settings['sv_os'] ?? $settings['sys_cpustring'] ?? $settings['mod_build'] ?? $settings['version'] ?? $server->os;

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

        // Count humans vs bots (bots report ping 0 in getstatus)
        $realPlayers = array_filter($players, fn($p) => ($p['ping'] ?? 0) > 0);
        $botPlayers  = array_filter($players, fn($p) => ($p['ping'] ?? 0) === 0);

        // Prefer explicit omnibot_playing setting when available, fallback to count of ping=0 entries
        $rawBotCount = isset($settings['omnibot_playing'])
            ? (int) $settings['omnibot_playing']
            : count($botPlayers);

        // current_players = humans + bots (matches how other trackers display it)
        $botCount = max(0, min(255, $rawBotCount));
        $totalPlayers = max(0, min(255, count($realPlayers) + $botCount));
        $maxPlayers = max(0, min(255, $maxPlayers));

        $privateSlots = isset($settings['sv_privateClients']) ? max(0, min(255, (int) $settings['sv_privateClients'])) : null;
        $latencyMs = isset($data['latency_ms']) ? max(0, min(65535, (int) $data['latency_ms'])) : null;

        // Server property flags from getstatus settings
        // g_friendlyFire is a bitfield on ETL; any non-zero value means FF is active
        $friendlyFire = isset($settings['g_friendlyFire'])
            ? ((int) $settings['g_friendlyFire']) > 0
            : null;
        $antilag = isset($settings['g_antilag']) ? (bool) (int) $settings['g_antilag'] : null;
        $balancedTeams = isset($settings['g_balancedteams']) ? (bool) (int) $settings['g_balancedteams'] : null;
        $heavyWeaponRestriction = isset($settings['g_heavyWeaponRestriction'])
            ? max(0, min(255, (int) $settings['g_heavyWeaponRestriction']))
            : null;

        // Anticheat detection based on well-known mod/tracker flags
        $anticheat = null;
        if (!empty($settings['sv_antiCheat']) && (int) $settings['sv_antiCheat'] > 0) {
            $anticheat = 'SilEnT AC';
        } elseif (!empty($settings['sv_NxAC'])) {
            $anticheat = 'NxAC';
        } elseif (!empty($settings['etns_version'])) {
            $anticheat = 'ETNS';
        }

        // g_oss bitfield: bit 0=Windows (1), bit 1=Mac (2), bit 2=Linux (4)
        $osSupport = isset($settings['g_oss']) ? ((int) $settings['g_oss']) & 0b111 : null;

        $server->update([
            'hostname' => $hostname,
            'hostname_clean' => ColorCodeService::toClean($hostname),
            'hostname_html' => ColorCodeService::toHtml($hostname),
            'current_map' => $map,
            'current_players' => $totalPlayers,
            'max_players' => $maxPlayers,
            'private_slots' => $privateSlots,
            'bot_count' => $botCount,
            'gametype' => $gametype,
            'mod_name' => $modName,
            'mod_version' => $modVersion,
            'game_id' => $server->game_id,
            'needs_password' => $password,
            'friendly_fire' => $friendlyFire,
            'antilag' => $antilag,
            'balanced_teams' => $balancedTeams,
            'heavy_weapon_restriction' => $heavyWeaponRestriction,
            'anticheat' => $anticheat,
            'sv_pure' => $pure,
            'punkbuster' => $punkbuster,
            'os' => $os,
            'os_support' => $osSupport,
            'latency_ms' => $latencyMs,
            'is_online' => true,
            'last_seen_at' => now(),
            'last_poll_at' => now(),
            'poll_failures' => 0,
            'status' => 'active',
        ]);

        // Update server settings (key-value store)
        $this->updateServerSettings($server, $settings);

        // Update map stats
        $this->updateMapStats($server, $map, $totalPlayers);

        // Self-heal: fetch geo info if this server never got one
        $this->ensureGeoInfo($server);
    }

    /**
     * If the server has no country_code yet, fetch geo info once.
     * Respects cache + rate limits via GeoIpService.
     */
    private function ensureGeoInfo(TrackerServer $server): void
    {
        if (!empty($server->country_code)) {
            return;
        }

        $geo = GeoIpService::lookup($server->ip);
        if ($geo === null) {
            return;
        }

        $server->update([
            'country' => $geo['country'] ?? null,
            'country_code' => $geo['country_code'] ?? null,
            'city' => $geo['city'] ?? null,
            'latitude' => $geo['latitude'] ?? null,
            'longitude' => $geo['longitude'] ?? null,
        ]);
    }

    /**
     * Store all server CVARs.
     */
    private function updateServerSettings(TrackerServer $server, array $settings): void
    {
        foreach ($settings as $key => $value) {
            TrackerServerSetting::updateOrCreate(
                ['server_id' => $server->id, 'key' => $this->toUtf8((string) $key)],
                ['value' => $this->toUtf8((string) $value), 'updated_at' => now()]
            );
        }
    }

    /**
     * Normalize game-server strings to valid UTF-8.
     *
     * ET/RtCW engines emit cvar values in Latin-1 / Windows-1252 by default.
     * MySQL utf8mb4 columns reject raw bytes like \xFC (u-umlaut), so we
     * convert any non-UTF-8 input before persisting to avoid
     * SQLSTATE[22007] "Incorrect string value" errors.
     */
    private function toUtf8(string $value): string
    {
        if ($value === '' || mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        return mb_convert_encoding($value, 'UTF-8', 'Windows-1252, ISO-8859-1');
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
     *
     * Polling interval is now based on server maturity so established servers
     * are still checked frequently and come back online automatically once
     * they reply to getstatus again.
     */
    private function handleOffline(TrackerServer $server): void
    {
        $server->increment('poll_failures');
        $server->refresh();

        // Mark offline after 3 consecutive failures
        if ($server->poll_failures >= 3) {
            $server->update([
                'is_online' => false,
                'current_players' => 0,
                'last_poll_at' => now(),
                'next_poll_at' => now()->addSeconds($this->offlinePollInterval($server)),
            ]);

            // End all active sessions on this server
            $this->playerTracker->endServerSessions($server);
        } else {
            $server->update([
                'last_poll_at' => now(),
                'next_poll_at' => now()->addSeconds($this->offlinePollInterval($server)),
            ]);
        }
    }

    /**
     * Offline polling interval in seconds, based on server maturity.
     *
     * - Established + enhanced tracker active: 2 min (very likely a real server)
     * - Established (>=1 day, was online):     3 min
     * - Probation (<1 day, was online once):   10 min
     * - Never verified online:                 30 min
     */
    private function offlinePollInterval(TrackerServer $server): int
    {
        $hasRecentEnhanced = $server->is_enhanced_tracker
            && !$server->enhanced_disabled
            && $server->enhanced_last_event_at
            && $server->enhanced_last_event_at->greaterThanOrEqualTo(now()->subHour());

        $established = $server->last_seen_at
            && $server->first_seen_at
            && $server->first_seen_at->lessThanOrEqualTo(now()->subDay());

        if ($established && $hasRecentEnhanced) return 120;
        if ($established)                      return 180;
        if ($server->last_seen_at)             return 600;
        return 1800;
    }
}