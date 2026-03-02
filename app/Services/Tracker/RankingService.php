<?php

namespace App\Services\Tracker;

use App\Models\Tracker\TrackerPlayer;
use App\Models\Tracker\TrackerRanking;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public function calculateRankings(string $period = 'daily', ?\Carbon\Carbon $date = null): int
    {
        $date = $date ?? now();
        $periodDate = $date->toDateString();

        $since = match ($period) {
            'daily'   => $date->copy()->subDay(),
            'weekly'  => $date->copy()->subWeek(),
            'monthly' => $date->copy()->subMonth(),
            'alltime' => null,
            default   => $date->copy()->subDay(),
        };

        // Delete existing for this period+date
        TrackerRanking::where('period', $period)->where('period_date', $periodDate)->delete();

        $query = TrackerPlayer::where('status', 'active')
            ->where('total_play_time_minutes', '>', 0)
            ->orderByDesc('elo_rating')
            ->orderByDesc('total_xp');

        $rank = 0;
        $inserted = 0;

        $query->chunkById(1000, function ($players) use ($period, $periodDate, $since, &$rank, &$inserted) {
            $rows = [];
            foreach ($players as $player) {
                $rank++;

                // Get session stats for this period
                $sessionQuery = DB::table('tracker_player_sessions')->where('player_id', $player->id);
                if ($since) $sessionQuery->where('started_at', '>=', $since);

                $sessionStats = $sessionQuery->selectRaw('
                    COUNT(*) as cnt, COALESCE(SUM(duration_minutes),0) as mins,
                    COALESCE(SUM(kills),0) as k, COALESCE(SUM(deaths),0) as d,
                    COALESCE(SUM(xp),0) as xp,
                    COUNT(DISTINCT server_id) as srv, COUNT(DISTINCT map_name) as maps
                ')->first();

                $rows[] = [
                    'player_id' => $player->id, 'period' => $period, 'period_date' => $periodDate,
                    'rank' => $rank, 'elo_rating' => $player->elo_rating, 'elo_change' => 0,
                    'total_xp' => $sessionStats->xp ?? $player->total_xp,
                    'playtime_minutes' => $sessionStats->mins ?? 0,
                    'sessions_count' => $sessionStats->cnt ?? 0,
                    'kills' => $sessionStats->k ?? 0, 'deaths' => $sessionStats->d ?? 0,
                    'servers_played' => $sessionStats->srv ?? 0, 'maps_played' => $sessionStats->maps ?? 0,
                    'created_at' => now(), 'updated_at' => now(),
                ];
                $inserted++;
            }
            if (!empty($rows)) TrackerRanking::insert($rows);
        });

        return $inserted;
    }

    public function getLeaderboard(string $period = 'alltime', int $perPage = 50)
    {
        $latestDate = TrackerRanking::where('period', $period)->max('period_date');
        if (!$latestDate) return TrackerRanking::where('id', 0)->paginate($perPage); // empty paginator

        return TrackerRanking::with('player')
            ->where('period', $period)
            ->where('period_date', $latestDate)
            ->orderBy('rank')
            ->paginate($perPage);
    }
}
