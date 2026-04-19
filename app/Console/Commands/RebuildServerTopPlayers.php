<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild top-8 all-time players per server snapshot.
 * Top-all-time is very stable (cumulative XP), so 30min schedule is plenty.
 */
class RebuildServerTopPlayers extends Command
{
    protected $signature = 'tracker:rebuild-top-players';
    protected $description = 'Rebuild materialized top-8 players snapshot per server';

    public function handle(): int
    {
        $start = microtime(true);
        $silent = $this->output->isQuiet();

        if (!$silent) $this->info('Computing top-players per server...');

        // Only servers with >= 10 sessions (otherwise not worth ranking)
        $serverIds = DB::table('tracker_player_sessions')
            ->select('server_id', DB::raw('COUNT(*) as sessions'))
            ->groupBy('server_id')
            ->havingRaw('COUNT(*) >= 10')
            ->pluck('server_id');

        DB::table('tracker_server_top_players')->truncate();
        $now = now();
        $totalInserted = 0;

        foreach ($serverIds as $serverId) {
            $top = DB::table('tracker_player_sessions')
                ->where('server_id', $serverId)
                ->join('tracker_players', 'tracker_players.id', '=', 'tracker_player_sessions.player_id')
                ->groupBy('tracker_players.id', 'tracker_players.name_clean', 'tracker_players.name_html')
                ->select(
                    'tracker_players.id as player_id',
                    'tracker_players.name_clean',
                    'tracker_players.name_html',
                    DB::raw('SUM(tracker_player_sessions.xp) as total_xp'),
                    DB::raw('SUM(tracker_player_sessions.duration_minutes) as total_min'),
                )
                ->orderByDesc('total_xp')
                ->limit(8)
                ->get();

            if ($top->isEmpty()) continue;

            $batch = [];
            $rank = 1;
            foreach ($top as $p) {
                $batch[] = [
                    'server_id'     => $serverId,
                    'player_id'     => $p->player_id,
                    'rank'          => $rank++,
                    'name_clean'    => $p->name_clean,
                    'name_html'     => $p->name_html,
                    'total_xp'      => (int) $p->total_xp,
                    'total_minutes' => (int) $p->total_min,
                    'computed_at'   => $now,
                ];
            }
            DB::table('tracker_server_top_players')->insert($batch);
            $totalInserted += count($batch);
        }

        $ms = round((microtime(true) - $start) * 1000, 0);
        if (!$silent) $this->info("Done: {$totalInserted} top-player rows for {$serverIds->count()} servers in {$ms}ms");
        return self::SUCCESS;
    }
}
