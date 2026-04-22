<?php
/**
 * Repair corrupted tracker_matches.started_at values.
 *
 * The ON UPDATE CURRENT_TIMESTAMP clause caused every UPDATE on a match row
 * (aggregation, end-reason backfill, etc.) to snap started_at to "now".
 * We now rebuild started_at from the original 'map' event in tracker_raw_events
 * that created the match in the first place.
 *
 * Strategy: for each match, find the latest 'map' or 'maprestart' event in
 * raw_events on the same server WHERE received_at <= (current corrupted
 * started_at OR corrupted ended_at — whichever is present). The very first
 * attribute we trust is (server_id, map_name); we look for the map event
 * matching both.
 *
 * Dry-run first — no updates happen unless --apply is passed.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

$apply = in_array('--apply', $argv, true);
$serverFilter = null;
foreach ($argv as $a) {
    if (preg_match('/^--server=(\d+)$/', $a, $m)) $serverFilter = (int) $m[1];
}

echo "=== tracker_matches started_at repair ===\n";
echo "Mode: " . ($apply ? 'APPLY' : 'DRY-RUN (use --apply to commit)') . "\n";
if ($serverFilter) echo "Server filter: {$serverFilter}\n";
echo "\n";

$q = DB::table('tracker_matches')->orderBy('id');
if ($serverFilter) $q->where('server_id', $serverFilter);
$matches = $q->get();

echo "Total matches: " . $matches->count() . "\n\n";

$counters = [
    'no_map_event'       => 0,
    'already_correct'    => 0,   // |delta| < 5s — no change needed
    'repaired'           => 0,
    'fallback_to_ended'  => 0,
    'backwards_ended_at' => 0,   // ended_at < started_at — need to swap/fix
];

$examples = [];

foreach ($matches as $m) {
    // Find the map event on the same server, same map_name, that happened
    // CLOSEST TO but NOT AFTER any kind of timestamp we can trust.
    //
    // If the corrupted started_at has sub-second cluster buddies, we can't
    // trust it directly. But ended_at is untouched (no ON UPDATE on that
    // one) — so use it as the upper bound.
    //
    // If ended_at is also corrupted (we saw some with ended_at < started_at),
    // fall back to just "latest map event matching (server, map)".

    $upperBound = $m->ended_at ?: $m->started_at;

    $mapEvent = DB::table('tracker_raw_events')
        ->where('server_id', $m->server_id)
        ->whereIn('cmd', ['map', 'maprestart'])
        ->where('payload', 'LIKE', '%' . $m->map_name . '%')
        ->where('received_at', '<=', $upperBound)
        ->orderByDesc('received_at')
        ->first(['id', 'received_at', 'payload', 'cmd']);

    // Fallback: no bound at all, grab anything matching
    if (!$mapEvent) {
        $mapEvent = DB::table('tracker_raw_events')
            ->where('server_id', $m->server_id)
            ->whereIn('cmd', ['map', 'maprestart'])
            ->where('payload', 'LIKE', '%' . $m->map_name . '%')
            ->orderBy('received_at')
            ->first(['id', 'received_at', 'payload', 'cmd']);
        if ($mapEvent) $counters['fallback_to_ended']++;
    }

    if (!$mapEvent) {
        $counters['no_map_event']++;
        continue;
    }

    $newStartedAt = $mapEvent->received_at;

    $delta = abs(strtotime($newStartedAt) - strtotime($m->started_at));
    if ($delta < 5) {
        $counters['already_correct']++;
        continue;
    }

    // Check ended_at sanity
    $newEndedAt = $m->ended_at;
    if ($newEndedAt !== null && strtotime($newEndedAt) < strtotime($newStartedAt)) {
        $counters['backwards_ended_at']++;
        // ended_at is before started_at → the ended_at is also corrupt.
        // Clear it; will be rebuilt from mapend events in a later pass.
        $newEndedAt = null;
    }

    // Recompute duration
    $newDuration = null;
    if ($newEndedAt !== null) {
        $newDuration = max(0, strtotime($newEndedAt) - strtotime($newStartedAt));
    }

    if (count($examples) < 8) {
        $examples[] = [
            'id' => $m->id,
            'map' => $m->map_name,
            'old_started' => $m->started_at,
            'new_started' => $newStartedAt,
            'old_ended'   => $m->ended_at,
            'new_ended'   => $newEndedAt,
            'delta_s'     => $delta,
        ];
    }

    if ($apply) {
        DB::table('tracker_matches')->where('id', $m->id)->update([
            'started_at'       => $newStartedAt,
            'ended_at'         => $newEndedAt,
            'duration_seconds' => $newDuration,
        ]);
    }
    $counters['repaired']++;
}

echo "=== Result ===\n";
foreach ($counters as $k => $v) echo "  " . str_pad($k, 22) . ": $v\n";

echo "\n=== Sample repairs ===\n";
foreach ($examples as $ex) {
    echo sprintf("  id=%d  %-20s  delta=%ds\n", $ex['id'], $ex['map'], $ex['delta_s']);
    echo sprintf("    OLD started=%s  ended=%s\n", $ex['old_started'], $ex['old_ended'] ?? '(null)');
    echo sprintf("    NEW started=%s  ended=%s\n", $ex['new_started'], $ex['new_ended'] ?? '(null)');
}

if (!$apply) {
    echo "\n(This was a dry-run. Add --apply to commit changes.)\n";
}
