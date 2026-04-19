<?php

namespace App\Services\Tracker;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Computes Classic Percentile-based ELO for Poller-tracked players.
 *
 * Why Percentile-based instead of traditional ELO:
 *   ET's getstatus protocol only gives us score/ping/name per player — no
 *   match-level win/loss, no K/D, no team. With just score+playtime we
 *   can't run ELO-math (K-factor * (actual - expected)). What we CAN do
 *   fairly is rank players by XP-per-minute and map their percentile
 *   position to a 0-2000 ELO scale.
 *
 * The formula (Classic variant):
 *   rate = total_xp / total_play_time_minutes
 *   percentile = rank_of(rate) / total_eligible_players
 *   elo = clamp(1000 + (percentile - 0.5) * 2000, 0, 2000)
 *
 * Eligibility:
 *   - Not a bot (is_bot = 0)
 *   - At least 60 minutes of total playtime (filters one-shot joiners)
 *   - total_xp > 0 (otherwise we have nothing to rank on)
 *
 * Players who don't meet eligibility get elo_rating = null (shown as
 * "Unrated" in the UI) rather than the misleading default of 1000.
 *
 * Mod-inflation protection:
 *   Percentile-rank is robust to outliers. A player with 300,000 xp/min
 *   on an XP-multiplier server ends up at P99.99 — same percentile slot
 *   as a strong vanilla player, not a 50x higher ELO.
 */
class EloService
{
    /** Minimum playtime in minutes for a player to be eligible. */
    private const MIN_PLAYTIME_MINUTES = 60;

    /** Cache key for the sorted rate-table (for on-the-fly single-player recalc). */
    private const RATE_TABLE_CACHE_KEY = 'tracker:elo:rate_table';

    /** Rate-table cache TTL in seconds. */
    private const RATE_TABLE_CACHE_TTL = 3600;

    /**
     * Recompute ELO for one specific player.
     *
     * Reads the (cached) sorted rate-table of all eligible players,
     * locates this player's position, and writes the computed ELO back
     * to tracker_players along with elo_updated_at.
     *
     * Returns the new ELO (null if player is ineligible).
     */
    public function calculateForPlayer(int $playerId): ?float
    {
        $p = DB::table('tracker_players')
            ->where('id', $playerId)
            ->first(['is_bot', 'total_xp', 'total_play_time_minutes', 'elo_rating', 'elo_peak']);

        if (!$p || $p->is_bot) {
            return null;
        }

        $rate = $this->rateFor($p);
        if ($rate === null) {
            return null;
        }

        $rates = $this->getRateTable();
        if (empty($rates)) {
            return null;
        }

        $elo = $this->eloFromRate($rate, $rates);

        $peak = max((float) ($p->elo_peak ?? 0), $elo);

        DB::table('tracker_players')
            ->where('id', $playerId)
            ->update([
                'elo_rating' => $elo,
                'elo_peak' => $peak,
                'elo_updated_at' => now()->format('Y-m-d H:i:s.v'),
                'updated_at' => now(),
            ]);

        return $elo;
    }

    /**
     * Bulk recompute ELO for every eligible player. Used by the daily cron.
     *
     * Returns the number of players updated.
     */
    public function calculateForAll(): int
    {
        // Always rebuild the rate-table fresh for a bulk run.
        Cache::forget(self::RATE_TABLE_CACHE_KEY);
        $rates = $this->getRateTable();

        if (empty($rates)) {
            return 0;
        }

        $count = $this->rateTableSize($rates);
        $updated = 0;
        $now = now()->format('Y-m-d H:i:s.v');

        DB::table('tracker_players')
            ->where('is_bot', 0)
            ->where('total_play_time_minutes', '>=', self::MIN_PLAYTIME_MINUTES)
            ->where('total_xp', '>', 0)
            ->orderBy('id')
            ->chunkById(1000, function ($players) use ($rates, $count, $now, &$updated) {
                foreach ($players as $p) {
                    $rate = (float) $p->total_xp / (float) $p->total_play_time_minutes;
                    $elo = $this->eloFromRate($rate, $rates);
                    $peak = max((float) ($p->elo_peak ?? 0), $elo);

                    DB::table('tracker_players')
                        ->where('id', $p->id)
                        ->update([
                            'elo_rating' => $elo,
                            'elo_peak' => $peak,
                            'elo_updated_at' => $now,
                            'updated_at' => $now,
                        ]);
                    $updated++;
                }
            });

        return $updated;
    }

    /**
     * Compute the rate (xp per minute) for a player, or null if ineligible.
     */
    private function rateFor(object $p): ?float
    {
        if (($p->total_play_time_minutes ?? 0) < self::MIN_PLAYTIME_MINUTES) {
            return null;
        }
        if (($p->total_xp ?? 0) <= 0) {
            return null;
        }
        return (float) $p->total_xp / (float) $p->total_play_time_minutes;
    }

    /**
     * Map a rate to an ELO value using the sorted rate-table.
     *
     * Binary search for the player's percentile position, then apply
     * the classic formula: elo = 1000 + (percentile - 0.5) * 2000.
     */
    private function eloFromRate(float $rate, array $rates): float
    {
        $n = count($rates);
        if ($n === 0) {
            return 1000.0;
        }

        // Binary search for the last index <= $rate.
        $lo = 0;
        $hi = $n - 1;
        $pos = 0;
        while ($lo <= $hi) {
            $mid = intdiv($lo + $hi, 2);
            if ($rates[$mid] <= $rate) {
                $pos = $mid + 1;
                $lo = $mid + 1;
            } else {
                $hi = $mid - 1;
            }
        }

        $percentile = $pos / $n;
        $elo = 1000 + ($percentile - 0.5) * 2000;
        return round(max(0.0, min(2000.0, $elo)), 2);
    }

    /**
     * Return the sorted rates (xp/min) of all eligible players. Cached.
     */
    private function getRateTable(): array
    {
        return Cache::remember(
            self::RATE_TABLE_CACHE_KEY,
            self::RATE_TABLE_CACHE_TTL,
            function () {
                $rows = DB::select(
                    'SELECT total_xp / total_play_time_minutes AS rate
                     FROM tracker_players
                     WHERE is_bot = 0
                       AND total_play_time_minutes >= ?
                       AND total_xp > 0
                     ORDER BY rate',
                    [self::MIN_PLAYTIME_MINUTES]
                );
                return array_map(fn ($r) => (float) $r->rate, $rows);
            }
        );
    }

    private function rateTableSize(array $rates): int
    {
        return count($rates);
    }

    /**
     * Invalidate the rate-table cache. Call after significant data changes
     * (e.g., a full session reprocess).
     */
    public function invalidateRateTable(): void
    {
        Cache::forget(self::RATE_TABLE_CACHE_KEY);
    }
}
