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

        DB::transaction(function () use ($realGuidHash, $pollerHash, $isBot, $now, $parsed) {
            // Stage 1: already-linked Enhanced player
            $player = DB::table('tracker_players')
                ->where('real_guid_hash', $realGuidHash)
                ->first(['id', 'enhanced_first_seen_at', 'is_bot']);

            if ($player !== null) {
                $this->updateExisting($player, $realGuidHash, $isBot, $now);
                return;
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
                return;
            }

            // Stage 3: no match, create fresh row
            // Still NO name/name_clean/name_html writes (Decision A).
            // But we do seed guid_hash = pollerHash so if the Poller ever sees
            // this player under the same clean-name, a future lookup collapses.
            DB::table('tracker_players')->insert([
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
        });
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
        // Disconnect carries only a slot — no way to attribute without in-memory
        // slot→player state. We just log for debugging.
        // Weapon stats before disconnect are captured via ws packets, so the
        // match stats are already preserved.
        Log::debug('Tracker disconnect', [
            'server_id' => $serverId,
            'payload' => $event->payload,
        ]);
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
}
