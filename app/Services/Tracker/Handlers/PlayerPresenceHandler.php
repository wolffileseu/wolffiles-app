<?php

namespace App\Services\Tracker\Handlers;

use App\Models\Tracker\TrackerRawEvent;
use App\Services\Tracker\PollerHashService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles player presence events from the Enhanced Tracker:
 *   - connect <slot> <GUID> <name>      e.g. "connect 8 090CE9B8...FD86 wahke"
 *   - disconnect <slot>                  e.g. "disconnect 7"
 *
 * 3-stage player matching on connect:
 *   1. Look up by real_guid_hash (sha256 of actual ET GUID)
 *      → hit: existing Enhanced-linked player, just bump fields.
 *   2. Look up by guid_hash (Poller's sha256 of name_clean)
 *      → hit: Poller player with the same clean-name. Enhance them:
 *             set real_guid_hash, has_enhanced_data=true.
 *   3. No match → create new player (likely a bot, or first-ever enhanced player).
 *
 * Following Decision A: we NEVER overwrite name / name_clean / name_html /
 * first_seen_at / last_seen_at on tracker_players. Those belong to the Poller.
 * We only touch enhanced_* fields plus real_guid_hash and is_bot.
 */
class PlayerPresenceHandler extends AbstractHandler
{
    private PollerHashService $hasher;

    public function __construct(?PollerHashService $hasher = null)
    {
        $this->hasher = $hasher ?? new PollerHashService();
    }

    public function supports(): array
    {
        return ['connect', 'disconnect'];
    }

    public function handle(TrackerRawEvent $event): void
    {
        $serverId = $event->server_id ?? $this->resolveServerId($event);
        if ($serverId === null) {
            return;
        }

        match ($event->cmd) {
            'connect'    => $this->handleConnect($event, $serverId),
            'disconnect' => $this->handleDisconnect($event, $serverId),
            default      => null,
        };
    }

    private function handleConnect(TrackerRawEvent $event, int $serverId): void
    {
        $parsed = $this->parseConnect($event->payload);
        if ($parsed === null) {
            Log::warning('PlayerPresenceHandler: malformed connect packet', [
                'payload' => substr($event->payload, 0, 200),
            ]);
            return;
        }

        $realGuidHash = $this->hasher->hashFromRealGuid($parsed['guid']);
        $pollerHash = $this->hasher->hashFromName($parsed['name']);
        $isBot = $this->nameLooksLikeBot($parsed['name']);
        $now = $event->received_at;

        // Skip bots entirely — their stats are not tracked.
        //
        // Rationale: Omnibot uses per-slot GUIDs (OMNIBOT07... for slot 7)
        // which collide across bots sharing a slot over time. Tracking them
        // would produce confusing cross-linked player rows. Since bot stats
        // are programmed behaviour and not of ranking interest, we drop
        // them at the handler boundary.
        if ($isBot) {
            Log::debug('PlayerPresenceHandler: skipping bot connect', [
                'slot' => $parsed['slot'],
                'name' => $parsed['name'],
            ]);
            return;
        }

        // Resolve or create the player, then record slot occupancy so that
        // WeaponStatsHandler can look up "who is on slot X" during ws packets.
        $playerId = DB::transaction(function () use ($realGuidHash, $pollerHash, $isBot, $now, $parsed) {
            // Stage 1: already-linked Enhanced player
            $player = DB::table('tracker_players')
                ->where('real_guid_hash', $realGuidHash)
                ->first(['id', 'enhanced_first_seen_at', 'is_bot']);

            if ($player !== null) {
                $this->updateExisting($player, $realGuidHash, $isBot, $now);
                return (int) $player->id;
            }

            // Stage 2: Poller match by name-clean hash
            $player = DB::table('tracker_players')
                ->where('guid_hash', $pollerHash)
                ->whereNull('real_guid_hash')   // not yet linked — avoid collisions
                ->first(['id', 'enhanced_first_seen_at', 'is_bot']);

            if ($player !== null) {
                $this->updateExisting($player, $realGuidHash, $isBot, $now);
                Log::info('PlayerPresenceHandler: linked Poller player to Enhanced GUID', [
                    'player_id' => $player->id,
                    'real_guid_hash_prefix' => substr($realGuidHash, 0, 8),
                ]);
                return (int) $player->id;
            }

            // Stage 3: no match, create fresh row
            // Still NO name/name_clean/name_html writes (Decision A).
            // But we do seed guid_hash = pollerHash so if the Poller ever sees
            // this player under the same clean-name, a future lookup collapses.
            $newId = DB::table('tracker_players')->insertGetId([
                'guid_hash' => $pollerHash,
                'real_guid_hash' => $realGuidHash,
                'name' => '',
                'name_clean' => '',
                'name_html' => '',
                'is_bot' => $isBot,
                'is_verified' => false,
                'status' => 'active',
                'has_enhanced_data' => true,
                'enhanced_first_seen_at' => $now,
                'enhanced_last_seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return (int) $newId;
        });

        // Record slot occupancy. Close any stale row on the same (server, slot)
        // first (in case a disconnect was missed) so only one row is open.
        $nowMs = $now->format('Y-m-d H:i:s.v');
        DB::table('tracker_server_slots')
            ->where('server_id', $serverId)
            ->where('slot', $parsed['slot'])
            ->whereNull('disconnected_at')
            ->update(['disconnected_at' => $nowMs, 'updated_at' => $nowMs]);

        DB::table('tracker_server_slots')->insert([
            'server_id' => $serverId,
            'slot' => $parsed['slot'],
            'player_id' => $playerId,
            'connected_at' => $nowMs,
            'disconnected_at' => null,
            'created_at' => $nowMs,
            'updated_at' => $nowMs,
        ]);
    }

    /**
     * Update an existing player row with Enhanced fields.
     * Never touches Poller-owned columns (name, first_seen_at, etc.).
     */
    private function updateExisting(\stdClass $player, string $realGuidHash, bool $isBot, \Illuminate\Support\Carbon $now): void
    {
        $updates = [
            'real_guid_hash' => $realGuidHash,
            'has_enhanced_data' => true,
            'enhanced_last_seen_at' => $now,
            'updated_at' => $now,
        ];
        if ($player->enhanced_first_seen_at === null) {
            $updates['enhanced_first_seen_at'] = $now;
        }
        // Bot detection is additive — never clear a previously-set flag.
        if ($isBot && !$player->is_bot) {
            $updates['is_bot'] = true;
        }

        DB::table('tracker_players')
            ->where('id', $player->id)
            ->update($updates);
    }

    private function handleDisconnect(TrackerRawEvent $event, int $serverId): void
    {
        // Parse "disconnect <slot>"
        if (!preg_match('/^disconnect\s+(\d+)\s*$/', $event->payload, $m)) {
            Log::debug('Tracker disconnect: malformed', ['payload' => $event->payload]);
            return;
        }
        $slot = (int) $m[1];

        $nowMs = $event->received_at->format('Y-m-d H:i:s.v');

        // Close the currently-open slot row. There should only ever be one
        // open at a time per (server, slot) — but update() handles zero and
        // many equally well without erroring.
        $affected = DB::table('tracker_server_slots')
            ->where('server_id', $serverId)
            ->where('slot', $slot)
            ->whereNull('disconnected_at')
            ->update([
                'disconnected_at' => $nowMs,
                'updated_at' => $nowMs,
            ]);

        if ($affected === 0) {
            Log::debug('Tracker disconnect: no open slot row to close', [
                'server_id' => $serverId,
                'slot' => $slot,
            ]);
        }
    }

    /**
     * Parse "connect <slot> <guid> <name...>".
     * Accepts hex GUIDs, Omni-Bot GUIDs (e.g. "OMNIBOT07000..."), and any
     * other non-whitespace GUID token.
     *
     * @return array{slot:int, guid:string, name:string}|null
     */
    private function parseConnect(string $payload): ?array
    {
        if (!preg_match('/^connect\s+(\d+)\s+(\S+)\s+(.*)$/s', $payload, $m)) {
            return null;
        }
        $guid = $m[2];
        // Sanity: reject absurdly long/short tokens that can't be real GUIDs
        if (strlen($guid) < 8 || strlen($guid) > 64) {
            return null;
        }
        return [
            'slot' => (int) $m[1],
            'guid' => $guid,
            'name' => rtrim($m[3]),
        ];
    }

    private function resolveServerId(TrackerRawEvent $event): ?int
    {
        $server = DB::table('tracker_servers')
            ->where(function ($query) use ($event) {
                $query->where('enhanced_source_ip', $event->source_ip)
                    ->orWhere('ip', $event->source_ip);
            })
            ->where('enhanced_disabled', false)
            ->first(['id']);

        return $server?->id;
    }

    // === BOT DETECTION HELPER (Commit 5) ===
    /**
     * Identify bots by any of the following markers:
     *   - real GUID contains 'BOT' (OmniBot: 000000000000000000000000BOT00018)
     *   - name starts with '[BOT]' (often set by admin in configs)
     *   - name starts with 'OmniBot' (default OmniBot naming)
     *
     * @param string|null $realGuid  Raw ET GUID (32 hex chars) or null
     * @param string|null $name      Player nickname (color-stripped)
     */
    public static function looksLikeBot(?string $realGuid, ?string $name): bool
    {
        if (is_string($realGuid) && stripos($realGuid, 'BOT') !== false) {
            return true;
        }
        if (is_string($name) && $name !== '') {
            if (stripos($name, '[BOT]') === 0) {
                return true;
            }
            if (stripos($name, 'OmniBot') === 0) {
                return true;
            }
        }
        return false;
    }

}
