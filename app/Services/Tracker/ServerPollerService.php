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
     * Increments the failure counter and (after 3 consecutive failures)
     * marks the server offline + closes its sessions. Does NOT set
     * next_poll_at — that's owned by PollServerJob now (single source
     * of truth, B-3b). Previously this method also wrote next_poll_at,
     * which PollServerJob then immediately overwrote — making
     * offlinePollInterval() effectively dead code.
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
            ]);

            // End all active sessions on this server
            $this->playerTracker->endServerSessions($server);
        } else {
            $server->update([
                'last_poll_at' => now(),
            ]);
        }
    }

    /**
     * Offline poll backoff in seconds, based on poll_failures.
     *
     * Public API — used by PollServerJob and may be used by admin actions
     * or future force-poll workflows.
     *
     * Staircase (flat, max 15min — game servers can recover any moment):
     *   1 fail   → 1 min   (60 s)
     *   2 fails  → 2 min   (120 s)
     *   3 fails  → 5 min   (300 s)
     *   4 fails  → 10 min  (600 s)
     *   5+ fails → 15 min  (900 s)   — hard cap
     *
     * Enhanced-tracker servers (recent UDP within 1h) are capped at 3 min
     * regardless of failure count — they've proven they're alive recently.
     *
     * Recovery: updateServerFromResponse() resets poll_failures to 0 on any
     * successful poll, so a returning server snaps back to the 5-min cadence
     * on its next failure (or stays online indefinitely).
     */
    public function offlinePollInterval(TrackerServer $server): int
    {
        // Admin override — bypasses failure backoff. Use with care: a
        // dead server set to interval=30 will burn 2 polls/min forever.
        if ($server->custom_poll_interval !== null) {
            return max(15, min(3600, (int) $server->custom_poll_interval));
        }

        $hasRecentEnhanced = $server->is_enhanced_tracker
            && !$server->enhanced_disabled
            && $server->enhanced_last_event_at
            && $server->enhanced_last_event_at->greaterThanOrEqualTo(now()->subHour());

        // Defensive: pollServer() called outside the normal job pipeline
        // could theoretically pass through with poll_failures=0. Fall back
        // to 5min (default branch) rather than masking unexpected state.
        $failures = max(1, (int) $server->poll_failures);

        $base = match (true) {
            $failures >= 5  => 900,     // 15 min — hard cap
            $failures === 4 => 600,     // 10 min
            $failures === 3 => 300,     // 5 min
            $failures === 2 => 120,     // 2 min
            default         => 60,      // 1 min — fail #1
        };

        if ($hasRecentEnhanced) {
            $base = min($base, 180);
        }

        return $base;
    }

    /**
     * Online poll cadence in seconds, based on current_players.
     *
     * Public API — used by PollServerJob and may be used by admin actions
     * or future force-poll workflows.
     *
     * Busier servers get polled more often so player snapshots stay dense
     * during active matches. Empty/idle servers fall back to the 5-min
     * baseline.
     */
    public function onlinePollInterval(TrackerServer $server): int
    {
        if ($server->custom_poll_interval !== null) {
            return max(15, min(3600, (int) $server->custom_poll_interval));
        }

        return match (true) {
            $server->current_players >= 15 => 30,
            $server->current_players >= 5  => 60,
            $server->current_players >= 1  => 120,
            default                        => 300,
        };
    }
}