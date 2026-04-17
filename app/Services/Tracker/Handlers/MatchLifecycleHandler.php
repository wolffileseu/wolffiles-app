<?php

namespace App\Services\Tracker\Handlers;

use App\Models\Tracker\TrackerRawEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles match lifecycle events:
 *   - map <name>     — a new map is loading / has loaded
 *   - maprestart     — same map restarted (round restart)
 *   - mapend         — map finished cleanly (objectives or timelimit)
 *
 * Responsibilities:
 *   - On 'map': close any open match for this server (mark as 'mapchange'),
 *               then open a fresh tracker_matches row.
 *   - On 'mapend': close the current match with end_reason='mapend'.
 *   - On 'maprestart': close current match with 'maprestart', open fresh.
 *     (ET treats maprestart as a new game-state, all weapon stats reset.)
 *
 * An "open" match = started_at set, ended_at is NULL.
 * Only one match may be open per server at any time.
 */
class MatchLifecycleHandler extends AbstractHandler
{
    public function supports(): array
    {
        return ['map', 'maprestart', 'mapend'];
    }

    public function handle(TrackerRawEvent $event): void
    {
        $serverId = $event->server_id ?? $this->resolveServerId($event);
        if ($serverId === null) {
            return;
        }

        match ($event->cmd) {
            'map'        => $this->handleMap($event, $serverId),
            'maprestart' => $this->handleMapRestart($event, $serverId),
            'mapend'     => $this->handleMapEnd($event, $serverId),
            default      => null,
        };
    }

    private function handleMap(TrackerRawEvent $event, int $serverId): void
    {
        $mapName = $this->parseMapName($event->payload);
        if ($mapName === null) {
            Log::warning('MatchLifecycleHandler: malformed map packet', [
                'payload' => substr($event->payload, 0, 200),
            ]);
            return;
        }

        DB::transaction(function () use ($event, $serverId, $mapName) {
            // Close any currently-open match for this server
            $this->closeOpenMatch($serverId, $event->received_at, 'mapchange');

            // Open a new match
            DB::table('tracker_matches')->insert([
                'server_id' => $serverId,
                'map_name' => $mapName,
                'started_at' => $event->received_at,
                'created_at' => $event->received_at,
                'updated_at' => $event->received_at,
            ]);
        });

        Log::info('Tracker match started', [
            'server_id' => $serverId,
            'map' => $mapName,
        ]);
    }

    private function handleMapEnd(TrackerRawEvent $event, int $serverId): void
    {
        DB::transaction(function () use ($event, $serverId) {
            $this->closeOpenMatch($serverId, $event->received_at, 'mapend');
        });

        Log::info('Tracker match ended (mapend)', [
            'server_id' => $serverId,
        ]);
    }

    private function handleMapRestart(TrackerRawEvent $event, int $serverId): void
    {
        DB::transaction(function () use ($event, $serverId) {
            // Close the current one as maprestart...
            $openMatch = $this->closeOpenMatch($serverId, $event->received_at, 'maprestart');

            // ...then open a fresh match on the same map (if we know it).
            if ($openMatch !== null) {
                DB::table('tracker_matches')->insert([
                    'server_id' => $serverId,
                    'map_name' => $openMatch->map_name,
                    'started_at' => $event->received_at,
                    'created_at' => $event->received_at,
                    'updated_at' => $event->received_at,
                ]);
            }
        });

        Log::info('Tracker match restarted (maprestart)', [
            'server_id' => $serverId,
        ]);
    }

    /**
     * Find and close any open match for this server.
     * Returns the closed match row (before closure) or null if none was open.
     */
    private function closeOpenMatch(int $serverId, Carbon $endedAt, string $reason): ?\stdClass
    {
        $open = DB::table('tracker_matches')
            ->where('server_id', $serverId)
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->first();

        if ($open === null) {
            return null;
        }

        $startedAt = Carbon::parse($open->started_at);

        // Defensive: if the received_at timestamp on the end-event is
        // somehow earlier than the start (clock skew, millisecond truncation
        // in an upstream layer), pin ended_at to the start so we don't store
        // negative durations.
        if ($endedAt->lessThan($startedAt)) {
            // If end timestamp is somehow earlier than start (rapid events,
            // millisecond precision issues on round-trip through MySQL),
            // pin ended to started + 1ms to ensure ended > started strictly.
            $endedAt = $startedAt->copy()->addMilliseconds(1);
        }

        // Millisecond-accurate duration via timestamp floats
        $durationMs = max(0, (int) round(($endedAt->getTimestamp() + $endedAt->micro / 1e6 - $startedAt->getTimestamp() - $startedAt->micro / 1e6) * 1000));
        $duration = (int) round($durationMs / 1000);

        DB::table('tracker_matches')
            ->where('id', $open->id)
            ->update([
                'ended_at' => $endedAt->format('Y-m-d H:i:s.v'),
                'duration_seconds' => $duration,
                'end_reason' => $reason,
                'updated_at' => $endedAt->format('Y-m-d H:i:s.v'),
            ]);

        // If there were any other "open" matches on this server (should not
        // happen but be defensive against daemon crashes), close them as timeout.
        DB::table('tracker_matches')
            ->where('server_id', $serverId)
            ->where('id', '!=', $open->id)
            ->whereNull('ended_at')
            ->update([
                'ended_at' => $endedAt->format('Y-m-d H:i:s.v'),
                'end_reason' => 'timeout',
                'updated_at' => $endedAt->format('Y-m-d H:i:s.v'),
            ]);

        return $open;
    }

    /**
     * Extract the map name from a "map <name>" payload.
     */
    private function parseMapName(string $payload): ?string
    {
        if (!preg_match('/^map\s+([a-z0-9_\-]+)\s*$/i', $payload, $m)) {
            return null;
        }
        return $m[1];
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
