<?php
/**
 * Rebuild tracker_player_weapon_stats (lifetime) from
 * tracker_match_player_weapon_stats (per-match).
 *
 * Our reprocess_ws.php only wrote per-match snapshots. The lifetime
 * aggregate used by the player profile UI never got updated, so
 * weapon breakdowns appear empty for reprocessed players.
 *
 * Strategy:
 *   1) Clear existing lifetime rows for affected (player, weapon_bit)
 *      combos — simpler than delta math.
 *   2) Re-aggregate from the per-match table: SUM across all matches
 *      for each (player, weapon_bit) pair.
 *   3) Compute accuracy_bp = floor(hits * 10000 / atts).
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$playerFilter = null;
foreach ($argv as $a) {
    if (preg_match('/^--player=(\d+)$/', $a, $m)) $playerFilter = (int) $m[1];
}

echo "=== Rebuild tracker_player_weapon_stats ===\n";
if ($playerFilter) echo "Player filter: {$playerFilter}\n";

$q = DB::table('tracker_match_player_weapon_stats')
    ->selectRaw('
        player_id, weapon_bit,
        SUM(hits) as hits, SUM(atts) as atts,
        SUM(kills) as kills, SUM(deaths) as deaths,
        SUM(headshots) as headshots,
        MIN(recorded_at) as first_seen,
        MAX(recorded_at) as last_updated
    ')
    ->groupBy('player_id', 'weapon_bit');
if ($playerFilter) $q->where('player_id', $playerFilter);

$aggregated = $q->get();
echo "Aggregated (player, weapon) combos: " . $aggregated->count() . "\n";

// Affected players — delete their existing lifetime rows
$playerIds = $aggregated->pluck('player_id')->unique();
if ($playerFilter) $playerIds = collect([$playerFilter]);

$deleted = DB::table('tracker_player_weapon_stats')
    ->whereIn('player_id', $playerIds)
    ->delete();
echo "Cleared old lifetime rows: {$deleted}\n";

// Insert aggregates
$now = now()->format('Y-m-d H:i:s.v');
$batch = [];
$inserted = 0;
foreach ($aggregated as $r) {
    $accuracy = $r->atts > 0 ? (int) floor($r->hits * 10000 / $r->atts) : 0;
    $batch[] = [
        'player_id'       => $r->player_id,
        'weapon_bit'      => $r->weapon_bit,
        'total_hits'      => (int) $r->hits,
        'total_atts'      => (int) $r->atts,
        'total_kills'     => (int) $r->kills,
        'total_deaths'    => (int) $r->deaths,
        'total_headshots' => (int) $r->headshots,
        'accuracy_bp'     => $accuracy,
        'first_seen_at'   => $r->first_seen ?? $now,
        'last_updated_at' => $r->last_updated ?? $now,
        'created_at'      => $now,
        'updated_at'      => $now,
    ];
    if (count($batch) >= 500) {
        DB::table('tracker_player_weapon_stats')->insert($batch);
        $inserted += count($batch);
        $batch = [];
    }
}
if (!empty($batch)) {
    DB::table('tracker_player_weapon_stats')->insert($batch);
    $inserted += count($batch);
}

echo "Inserted lifetime rows: {$inserted}\n";

if ($playerFilter) {
    echo "\n=== Verify ===\n";
    $rows = DB::table('tracker_player_weapon_stats')->where('player_id', $playerFilter)->orderByDesc('total_kills')->get();
    foreach ($rows as $x) {
        echo sprintf("  bit=%d kills=%d hits=%d atts=%d hs=%d acc=%.2f%%\n",
            $x->weapon_bit, $x->total_kills, $x->total_hits, $x->total_atts, $x->total_headshots,
            $x->accuracy_bp / 100);
    }
}
