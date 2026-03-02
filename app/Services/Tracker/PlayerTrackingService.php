<?php

namespace App\Services\Tracker;

use App\Models\Tracker\TrackerPlayer;
use App\Models\Tracker\TrackerPlayerAlias;
use App\Models\Tracker\TrackerPlayerSession;
use App\Models\Tracker\TrackerPlayerSnapshot;
use App\Models\Tracker\TrackerServer;
use Illuminate\Support\Facades\Log;

class PlayerTrackingService
{
    /**
     * Process the player list from a server poll.
     */
    public function processPlayerList(TrackerServer $server, array $players, array $settings = []): void
    {
        $activeSessions = TrackerPlayerSession::where('server_id', $server->id)
            ->whereNull('ended_at')
            ->get()
            ->keyBy('player_id');

        $seenPlayerIds = [];

        foreach ($players as $playerData) {
            // Skip bots (ping 0)
            if (($playerData['ping'] ?? 0) <= 0) continue;

            $name = $playerData['name'] ?? 'Unknown';
            $score = $playerData['score'] ?? 0;
            $ping = $playerData['ping'] ?? 0;

            // Generate a GUID hash from name + server IP (fallback when no real GUID)
            // Real GUIDs would come from server logs or RCON
            $guidHash = $this->generateGuidHash($name, $server->ip);

            // Find or create player
            $player = $this->findOrCreatePlayer($guidHash, $name, $server->ip);
            $seenPlayerIds[] = $player->id;

            // Track alias
            $this->trackAlias($player, $name);

            // Handle session
            if ($activeSessions->has($player->id)) {
                // Update existing session
                $session = $activeSessions->get($player->id);
                $this->updateSession($session, $playerData);
            } else {
                // Start new session
                $session = $this->startSession($player, $server);
            }

            // Save snapshot
            TrackerPlayerSnapshot::create([
                'session_id' => $session->id,
                'server_id' => $server->id,
                'player_id' => $player->id,
                'name' => $name,
                'score' => $score,
                'ping' => $ping,
                'team' => $this->detectTeam($playerData, $settings),
                'polled_at' => now(),
            ]);

            // Update player last seen
            $player->update(['last_seen_at' => now()]);
        }

        // End sessions for players no longer on server
        foreach ($activeSessions as $playerId => $session) {
            if (!in_array($playerId, $seenPlayerIds)) {
                $this->endSession($session);
            }
        }
    }

    /**
     * Find or create a player by GUID hash.
     */
    public function findOrCreatePlayer(string $guidHash, string $name, string $ip): TrackerPlayer
    {
        $player = TrackerPlayer::where('guid_hash', $guidHash)->first();

        if ($player) {
            // Update name if changed
            $cleanName = ColorCodeService::toClean($name);
            if ($player->name_clean !== $cleanName) {
                $player->update([
                    'name' => $name,
                    'name_clean' => $cleanName,
                    'name_html' => ColorCodeService::toHtml($name),
                ]);
            }
            return $player;
        }

        // Look up geo data
        $geo = GeoIpService::lookup($ip);

        return TrackerPlayer::create([
            'guid_hash' => $guidHash,
            'name' => $name,
            'name_clean' => ColorCodeService::toClean($name),
            'name_html' => ColorCodeService::toHtml($name),
            'country' => $geo['country'] ?? null,
            'country_code' => $geo['country_code'] ?? null,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'elo_rating' => 1000.00,
            'elo_peak' => 1000.00,
        ]);
    }

    /**
     * Start a new session for a player on a server.
     */
    public function startSession(TrackerPlayer $player, TrackerServer $server): TrackerPlayerSession
    {
        $player->increment('total_sessions');

        return TrackerPlayerSession::create([
            'player_id' => $player->id,
            'server_id' => $server->id,
            'game_id' => $server->game_id,
            'started_at' => now(),
            'map_name' => $server->current_map,
        ]);
    }

    /**
     * Update an active session with new snapshot data.
     */
    public function updateSession(TrackerPlayerSession $session, array $playerData): void
    {
        $score = $playerData['score'] ?? 0;
        $previousScore = $session->score;

        // Calculate kills/deaths from score changes (approximate)
        $scoreDiff = $score - $previousScore;
        if ($scoreDiff > 0) {
            $session->increment('kills', $scoreDiff);
        }

        $session->update([
            'score' => $score,
            'duration_minutes' => (int)$session->started_at->diffInMinutes(now()),
        ]);
    }

    /**
     * End a session (player left server).
     */
    public function endSession(TrackerPlayerSession $session): void
    {
        $duration = (int)$session->started_at->diffInMinutes(now());

        $session->update([
            'ended_at' => now(),
            'duration_minutes' => $duration,
        ]);

        // Update player totals
        $player = $session->player;
        $player->increment('total_play_time_minutes', $duration);
        $player->increment('total_kills', $session->kills);
        $player->increment('total_deaths', $session->deaths);
        $player->increment('total_sessions');
        $player->update(['last_seen_at' => now()]);
    }

    /**
     * End all active sessions on a server (server went offline).
     */
    public function endServerSessions(TrackerServer $server): void
    {
        $sessions = TrackerPlayerSession::where('server_id', $server->id)
            ->whereNull('ended_at')
            ->get();

        foreach ($sessions as $session) {
            $this->endSession($session);
        }
    }

    /**
     * End stale sessions (no update for X minutes).
     */
    public function endStaleSessions(int $minutesThreshold = 10): int
    {
        $staleSessions = TrackerPlayerSession::whereNull('ended_at')
            ->where('started_at', '<', now()->subMinutes($minutesThreshold))
            ->whereDoesntHave('snapshots', function ($q) use ($minutesThreshold) {
                $q->where('polled_at', '>=', now()->subMinutes($minutesThreshold));
            })
            ->get();

        foreach ($staleSessions as $session) {
            $this->endSession($session);
        }

        return $staleSessions->count();
    }

    /**
     * Track a player alias.
     */
    public function trackAlias(TrackerPlayer $player, string $name): void
    {
        $cleanName = ColorCodeService::toClean($name);

        $alias = TrackerPlayerAlias::where('player_id', $player->id)
            ->where('name_clean', $cleanName)
            ->first();

        if ($alias) {
            $alias->increment('times_used');
            $alias->update(['last_seen_at' => now()]);
        } else {
            TrackerPlayerAlias::create([
                'player_id' => $player->id,
                'name' => $name,
                'name_clean' => $cleanName,
                'name_html' => ColorCodeService::toHtml($name),
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);
        }
    }

    /**
     * Generate a GUID hash for player identification.
     * Note: Without RCON access, we use name + server IP as fallback.
     * With RCON or server logs, real GUIDs should be used.
     */
    private function generateGuidHash(string $name, string $serverIp): string
    {
        $cleanName = ColorCodeService::toClean($name);
        return hash('sha256', strtolower($cleanName) . ':' . $serverIp);
    }

    /**
     * Detect player team from settings/data.
     */
    private function detectTeam(array $playerData, array $settings): ?string
    {
        // Some mods include team info in player data
        if (isset($playerData['team'])) {
            return match((int)$playerData['team']) {
                1 => 'axis',
                2 => 'allies',
                3 => 'spectator',
                default => null,
            };
        }

        return null;
    }
}
