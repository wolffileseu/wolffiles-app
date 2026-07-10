<?php

namespace App\Services\Tracker\Handlers;

use stdClass;
use App\Models\Tracker\TrackerRawEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\Tracker\RebuildPlayerRankings30dJob;

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

            // Open a new match (use formatted string to preserve milliseconds —
            // Carbon objects in insert() get coerced to Y-m-d H:i:s without .v)
            $startedAtMs = $event->received_at->format('Y-m-d H:i:s.v');
            $startCounts = $this->teamSnapshotCounts($serverId);
            DB::table('tracker_matches')->insert([
                'server_id' => $serverId,
                'map_name' => $mapName,
                'started_at' => $startedAtMs,
                'players_at_start' => $startCounts['playing'],
                'allies_at_start' => $startCounts['allies'],
                'axis_at_start' => $startCounts['axis'],
                'spec_at_start' => $startCounts['spec'],
                'created_at' => $startedAtMs,
                'updated_at' => $startedAtMs,
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
                $startedAtMs = $event->received_at->format('Y-m-d H:i:s.v');
                DB::table('tracker_matches')->insert([
                    'server_id' => $serverId,
                    'map_name' => $openMatch->map_name,
                    'started_at' => $startedAtMs,
                    'created_at' => $startedAtMs,
                    'updated_at' => $startedAtMs,
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
    /**
     * Team distribution of currently-connected human players (non-bots),
     * from the latest poll snapshot per open session (getstatus/getinfo).
     * tracker_player_snapshots.team holds 'allies' | 'axis' | 'spectator'.
     * Returns ['allies'=>int,'axis'=>int,'spec'=>int,'playing'=>int].
     */
    private function teamSnapshotCounts(int $serverId): array
    {
        $out = ['allies' => 0, 'axis' => 0, 'spec' => 0, 'playing' => 0];

        $sessionIds = DB::table('tracker_player_sessions')
            ->where('server_id', $serverId)
            ->whereNull('ended_at')
            ->pluck('id')
            ->all();

        if (empty($sessionIds)) {
            return $out;
        }

        $rows = DB::table('tracker_player_snapshots as s')
            ->join('tracker_player_sessions as ses', 'ses.id', '=', 's.session_id')
            ->leftJoin('tracker_players as p', 'p.id', '=', 'ses.player_id')
            ->whereIn('s.session_id', $sessionIds)
            ->where(function ($q) {
                $q->where('p.is_bot', 0)->orWhereNull('p.is_bot');
            })
            ->whereRaw('s.polled_at = (SELECT MAX(polled_at) FROM tracker_player_snapshots WHERE session_id = s.session_id)')
            ->get(['s.team']);

        foreach ($rows as $r) {
            switch ((string) $r->team) {
                case 'allies':    $out['allies']++; break;
                case 'axis':      $out['axis']++;   break;
                case 'spectator': $out['spec']++;   break;
            }
        }
        $out['playing'] = $out['allies'] + $out['axis'];

        return $out;
    }

    /**
     * Debounced trigger for the 30-day player-ranking (ELO) rebuild.
     *
     * Fires whenever a match closes. A short cache lock lets only the first
     * close within a ~60s window schedule a job; the job runs ~20s later (so
     * straggler ws-events have landed). At most one rebuild per ~60s; the
     * 30-minute scheduled rebuild stays as a safety net. afterCommit() so it
     * never fires on a rolled-back transaction.
     */
    private function scheduleRankingRebuild(): void
    {
        if (Cache::add('tracker:rankings:rebuild-lock', 1, now()->addSeconds(60))) {
            RebuildPlayerRankings30dJob::dispatch()
                ->onQueue('tracker-low')
                ->afterCommit()
                ->delay(now()->addSeconds(20));
        }
    }

    private function closeOpenMatch(int $serverId, Carbon $endedAt, string $reason): ?stdClass
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
            $endedAt = $startedAt->copy()->addMilliseconds(10);
        }

        // Millisecond-accurate duration via timestamp floats
        $durationMs = max(0, (int) round(($endedAt->getTimestamp() + $endedAt->micro / 1e6 - $startedAt->getTimestamp() - $startedAt->micro / 1e6) * 1000));
        $duration = (int) round($durationMs / 1000);

        $updateData = [
            'ended_at' => $endedAt->format('Y-m-d H:i:s.v'),
            'duration_seconds' => $duration,
            'end_reason' => $reason,
            'updated_at' => $endedAt->format('Y-m-d H:i:s.v'),
        ];
        // Snapshot connected humans at the moment the map truly ends.
        // For mapchange/maprestart this isn't a real finish, so leave it null.
        if ($reason === 'mapend') {
            $endCounts = $this->teamSnapshotCounts($serverId);
            $updateData['players_at_end'] = $endCounts['playing'];
            $updateData['allies_at_end'] = $endCounts['allies'];
            $updateData['axis_at_end'] = $endCounts['axis'];
            $updateData['spec_at_end'] = $endCounts['spec'];
        }

        DB::table('tracker_matches')
            ->where('id', $open->id)
            ->update($updateData);

        // Reconstruct aggregate match result from per-player stats.
        // The sv_tracker protocol does not emit a final scoreboard/score event,
        // so we roll up tracker_player_match_stats (populated by ws-events) into
        // the parent match row. Runs after duration is set so UI can filter by
        // "has result".
        $this->aggregateMatchResult($open->id);

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

        $this->scheduleRankingRebuild();

        return $open;
    }

    /**
     * Extract the map name from a "map <name>" payload.
     */
    /**
     * Aggregate match-level totals from per-player ws-event stats.
     *
     * team 0 = spectator, 1 = Axis, 2 = Allies in ET. We sum kills/deaths per
     * team, plus overall totals. Written to tracker_matches.total_kills,
     * total_deaths, player_count_max, and a JSON final_scoreboard with
     * per-team breakdown + winner heuristic (kills-per-player, handles uneven
     * team sizes better than raw kill totals).
     */
    private function aggregateMatchResult(int $matchId): void
    {
        $stats = DB::table('tracker_player_match_stats')
            ->where('match_id', $matchId)
            ->selectRaw("
                SUM(kills) as total_kills,
                SUM(deaths) as total_deaths,
                COUNT(*) as player_count,
                SUM(CASE WHEN team = 1 THEN kills ELSE 0 END) as axis_kills,
                SUM(CASE WHEN team = 2 THEN kills ELSE 0 END) as allies_kills,
                SUM(CASE WHEN team = 1 THEN deaths ELSE 0 END) as axis_deaths,
                SUM(CASE WHEN team = 2 THEN deaths ELSE 0 END) as allies_deaths,
                SUM(CASE WHEN team = 1 THEN 1 ELSE 0 END) as axis_players,
                SUM(CASE WHEN team = 2 THEN 1 ELSE 0 END) as allies_players,
                SUM(objectives_taken) as total_objectives
            ")
            ->first();

        if ($stats === null || (int) $stats->player_count === 0) {
            return; // no player stats recorded — leave match totals as-is
        }

        $axisScore   = $stats->axis_players   > 0 ? $stats->axis_kills   / $stats->axis_players   : 0;
        $alliesScore = $stats->allies_players > 0 ? $stats->allies_kills / $stats->allies_players : 0;
        $winner = null;
        if ($axisScore > $alliesScore) {
            $winner = 'axis';
        } elseif ($alliesScore > $axisScore) {
            $winner = 'allies';
        } elseif ($stats->axis_players > 0 || $stats->allies_players > 0) {
            $winner = 'draw';
        }

        $scoreboard = [
            'axis' => [
                'kills'   => (int) $stats->axis_kills,
                'deaths'  => (int) $stats->axis_deaths,
                'players' => (int) $stats->axis_players,
            ],
            'allies' => [
                'kills'   => (int) $stats->allies_kills,
                'deaths'  => (int) $stats->allies_deaths,
                'players' => (int) $stats->allies_players,
            ],
            'winner'     => $winner,
            'objectives' => (int) $stats->total_objectives,
            'source'     => 'ws-aggregate',
        ];

        DB::table('tracker_matches')
            ->where('id', $matchId)
            ->update([
                'total_kills'      => (int) $stats->total_kills,
                'total_deaths'     => (int) $stats->total_deaths,
                'player_count_max' => (int) $stats->player_count,
                'final_scoreboard' => json_encode($scoreboard),
            ]);

        Log::info('Tracker match aggregated', [
            'match_id' => $matchId,
            'kills'    => (int) $stats->total_kills,
            'winner'   => $winner,
        ]);
    }

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
