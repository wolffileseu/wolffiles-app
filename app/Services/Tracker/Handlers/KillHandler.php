<?php

namespace App\Services\Tracker\Handlers;

use App\Models\Tracker\TrackerRawEvent;
use App\Services\Tracker\RtcwMeansOfDeath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles 'kill' events from RtCW (iortcw) servers.
 *
 * Wire format (one per kill, parsed by the engine from the game's "Kill:"
 * obituary log line and forwarded as an OOB packet):
 *
 *     kill <killerSlot> <victimSlot> <mod>
 *
 * e.g. "kill 5 2 18"  -> slot 5 killed slot 2 with MOD_THOMPSON (index 18)
 *
 * This is the RtCW counterpart to ET's weapon-stats. RtCW + Omnibot does not
 * implement the ET statsall/ws path, so we derive K/D and weapon usage from
 * individual kills instead. Stored in its OWN table (tracker_rtcw_kills) — the
 * ET weapon-stats tables are left completely untouched.
 *
 * Not available for RtCW (documented limitations):
 *   - team / team-kill detection (sess.sessionTeam is mod-side, not in engine)
 *   - accuracy / hits / shots / damage (no ws path)
 *
 * Slot resolution mirrors WeaponStatsHandler: open rows in tracker_server_slots
 * (disconnected_at IS NULL). A slot with no open row is treated as a bot /
 * untracked player and stored with a null player_id (still counted as an event).
 */
class KillHandler extends AbstractHandler
{
    public function supports(): array
    {
        return ['kill'];
    }

    public function handle(TrackerRawEvent $event): void
    {
        $parsed = $this->parseKill($event->payload);
        if ($parsed === null) {
            Log::warning('KillHandler: unparsable kill payload', [
                'event_id' => $event->id,
                'payload'  => substr($event->payload, 0, 120),
            ]);
            return;
        }

        $serverId = $event->server_id ?? $this->resolveServerId($event);
        if ($serverId === null) {
            Log::debug('KillHandler: no server_id for event', ['event_id' => $event->id]);
            return;
        }

        $mod      = $parsed['mod'];
        $info     = RtcwMeansOfDeath::resolve($mod);
        $isFrag   = $info['category'] === 'weapon';
        $isWorld  = in_array($info['category'], ['environment', 'suicide'], true);

        // Resolve killer/victim slots to players (open slot rows only).
        [$killerPlayerId, $killerIsBot] = $this->resolveSlot($serverId, $parsed['killer']);
        [$victimPlayerId, $victimIsBot] = $this->resolveSlot($serverId, $parsed['victim']);

        // For world/suicide deaths the killer slot is meaningless (often equal
        // to the victim, or a world sentinel). Null the killer so it can't be
        // miscredited a frag.
        if ($isWorld) {
            $killerPlayerId = null;
            $killerIsBot    = false;
            $isFrag         = false;
        }

        // Self-kill with a weapon (rare) is not a frag either.
        if ($isFrag && $parsed['killer'] === $parsed['victim']) {
            $isFrag = false;
        }

        $match = DB::table('tracker_matches')
            ->where('server_id', $serverId)
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first(['id']);

        $matchId = $match->id ?? null;

        DB::transaction(function () use (
            $event, $serverId, $matchId, $parsed, $mod, $info,
            $killerPlayerId, $victimPlayerId, $killerIsBot, $victimIsBot,
            $isFrag, $isWorld
        ) {
            DB::table('tracker_rtcw_kills')->insert([
                'server_id'        => $serverId,
                'match_id'         => $matchId,
                'killer_slot'      => $parsed['killer'],
                'victim_slot'      => $parsed['victim'],
                'killer_player_id' => $killerPlayerId,
                'victim_player_id' => $victimPlayerId,
                'mod_index'        => $mod,
                'weapon_key'       => $info['key'],
                'category'         => $info['category'],
                'is_frag'          => $isFrag,
                'is_world'         => $isWorld,
                'killer_is_bot'    => $killerIsBot,
                'victim_is_bot'    => $victimIsBot,
                'killed_at'        => $event->received_at,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        });
    }

    /**
     * Resolve a slot to [player_id|null, isBot].
     *
     * Mirrors WeaponStatsHandler: the most recent still-open slot row wins.
     * No open row -> bot or untracked -> [null, true-ish]. We can't be 100%
     * sure it's a bot, but for RtCW the connect handler only records real
     * players in tracker_server_slots, so "no row" strongly implies a bot.
     *
     * @return array{0:?int,1:bool}
     */
    private function resolveSlot(int $serverId, int $slot): array
    {
        // World/sentinel slots (RtCW uses high values like 1022/1023 for world)
        if ($slot < 0 || $slot > 63) {
            return [null, false];
        }

        $row = DB::table('tracker_server_slots')
            ->where('server_id', $serverId)
            ->where('slot', $slot)
            ->whereNull('disconnected_at')
            ->orderByDesc('connected_at')
            ->first(['player_id']);

        if ($row === null) {
            // No tracked player on this slot -> treat as bot.
            return [null, true];
        }

        return [(int) $row->player_id, false];
    }

    /**
     * Parse "kill <killer> <victim> <mod>" into ints.
     *
     * @return array{killer:int,victim:int,mod:int}|null
     */
    private function parseKill(string $payload): ?array
    {
        // payload includes the leading command word "kill"
        if (!preg_match('/^kill\s+(\d+)\s+(\d+)\s+(\d+)\s*$/', trim($payload), $m)) {
            return null;
        }

        return [
            'killer' => (int) $m[1],
            'victim' => (int) $m[2],
            'mod'    => (int) $m[3],
        ];
    }
}
