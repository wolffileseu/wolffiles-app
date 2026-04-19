<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild tracker_server_rankings from scratch.
 * Runs the expensive aggregate once, stores positions per server.
 *
 * Schedule this every 10 minutes — the ranking snapshot is much cheaper
 * to read per-request than computing the aggregate on every embed load.
 */
class RebuildServerRankings extends Command
{
    protected $signature = 'tracker:rebuild-rankings';
    protected $description = 'Rebuild materialized server-ranking snapshot (per game, 30d avg players)';

    public function handle(): int
    {
        $start = microtime(true);
        $silent = $this->output->isQuiet();

        if (!$silent) $this->info('Computing rankings per game...');

        // Group by game_id, compute ranks within each game
        $games = DB::table('tracker_servers')->distinct()->pluck('game_id');
        $totalInserted = 0;

        // TRUNCATE is DDL and auto-commits, so no DB::transaction here.
        DB::table('tracker_server_rankings')->truncate();
        $now = now();

        $runInner = function () use ($games, &$totalInserted, $silent, $now) {

            foreach ($games as $gameId) {
                $rows = DB::table('tracker_server_history')
                    ->join('tracker_servers', 'tracker_servers.id', '=', 'tracker_server_history.server_id')
                    ->where('tracker_servers.game_id', $gameId)
                    ->where('polled_at', '>=', now()->subDays(30))
                    ->groupBy('server_id')
                    ->havingRaw('COUNT(*) > 10')
                    ->select(
                        'server_id',
                        DB::raw('AVG(players) as avg_p'),
                        DB::raw('COUNT(*) as polls'),
                    )
                    ->orderByDesc('avg_p')
                    ->get();

                $total = $rows->count();
                if ($total === 0) continue;

                $batch = [];
                $pos = 1;
                foreach ($rows as $r) {
                    $batch[] = [
                        'server_id'       => $r->server_id,
                        'game_id'         => $gameId,
                        'rank'            => $pos++,
                        'total_in_game'   => $total,
                        'avg_players_30d' => round($r->avg_p, 2),
                        'polls_counted'   => $r->polls,
                        'computed_at'     => $now,
                    ];
                }

                // Batch insert in chunks (MySQL max_allowed_packet safety)
                foreach (array_chunk($batch, 500) as $chunk) {
                    DB::table('tracker_server_rankings')->insert($chunk);
                }
                $totalInserted += count($batch);

                if (!$silent) {
                    $this->line("  game {$gameId}: {$total} servers ranked");
                }
            }
        };
        $runInner();

        $ms = round((microtime(true) - $start) * 1000, 0);
        if (!$silent) $this->info("Done: {$totalInserted} servers ranked in {$ms}ms");
        return self::SUCCESS;
    }
}
