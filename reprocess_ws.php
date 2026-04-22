<?php
/**
 * Reprocess ws-events with time-based slot lookup.
 *
 * Why this exists:
 * The live WeaponStatsHandler looks up "who is on slot X right now" via
 * whereNull(disconnected_at). That's correct for live events but wrong for
 * historical reprocessing, because all slots are eventually closed.
 *
 * This script does what the handler would do, except it looks up the slot
 * assignment by TIME (which player was on slot X when the event was
 * received?) so we can rebuild stats from raw_events after slot-table
 * corruption has been fixed.
 *
 * Usage:
 *   php reprocess_ws.php <server_id> [<from_datetime> [<to_datetime>]]
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\Tracker\WeaponStatsParser;

$serverId = (int) ($argv[1] ?? 26510);
$from = $argv[2] ?? null;
$to = $argv[3] ?? null;

echo "=== Reprocess ws-events Server {$serverId} ===\n";
if ($from) echo "From: {$from}\n";
if ($to) echo "To:   {$to}\n";

$q = DB::table('tracker_raw_events')
    ->where('server_id', $serverId)
    ->where('cmd', 'ws')
    ->orderBy('received_at');
if ($from) $q->where('received_at', '>=', $from);
if ($to)   $q->where('received_at', '<=', $to);

$events = $q->get();
echo "Events to process: " . $events->count() . "\n\n";

$parser = new WeaponStatsParser();

$counters = [
    'parsed'            => 0,
    'parse_null'        => 0,
    'no_slot_match'     => 0,
    'no_open_match'     => 0,
    'stats_written'     => 0,
    'weapons_written'   => 0,
    'empty_mask_skip'   => 0,
];

foreach ($events as $e) {
    $parsed = $parser->parse($e->payload);
    if ($parsed === null) { $counters['parse_null']++; continue; }
    $counters['parsed']++;

    // Zeitbasierter Slot-Lookup
    $slotRow = DB::table('tracker_server_slots')
        ->where('server_id', $serverId)
        ->where('slot', $parsed['slot'])
        ->where('connected_at', '<=', $e->received_at)
        ->where(function ($q) use ($e) {
            $q->whereNull('disconnected_at')
              ->orWhere('disconnected_at', '>=', $e->received_at);
        })
        ->orderByDesc('connected_at')
        ->first(['player_id']);

    if (!$slotRow) { $counters['no_slot_match']++; continue; }

    // Skip packets with no actual weapon data — these overwrite good
    // snapshots on the same (match_id, slot) upsert key with zeroes.
    // Typically emitted by the server for spectators or at map end
    // after stats have been reset.
    if (($parsed['weapon_mask'] ?? 0) === 0) { $counters['empty_mask_skip'] = ($counters['empty_mask_skip'] ?? 0) + 1; continue; }
    $playerId = (int) $slotRow->player_id;

    // Match zum Zeitpunkt des Events finden
    $match = DB::table('tracker_matches')
        ->where('server_id', $serverId)
        ->where('started_at', '<=', $e->received_at)
        ->where(function ($q) use ($e) {
            $q->whereNull('ended_at')
              ->orWhere('ended_at', '>=', $e->received_at);
        })
        ->orderByDesc('started_at')
        ->first(['id', 'map_name']);

    if (!$match) { $counters['no_open_match']++; continue; }

    $recv = Carbon::parse($e->received_at);
    $nowMs = $recv->format('Y-m-d H:i:s.v');

    // Weapon-Snapshots schreiben
    foreach ($parsed['weapons'] as $wbit => $w) {
        $accuracy = $w['atts'] > 0 ? (int) floor($w['hits'] * 10000 / $w['atts']) : 0;
        DB::table('tracker_match_player_weapon_stats')->upsert(
            [[
                'match_id' => $match->id,
                'player_id' => $playerId,
                'weapon_bit' => $wbit,
                'hits' => $w['hits'],
                'atts' => $w['atts'],
                'kills' => $w['kills'],
                'deaths' => $w['deaths'],
                'headshots' => $w['headshots'],
                'accuracy_bp' => $accuracy,
                'recorded_at' => $nowMs,
                'created_at' => $nowMs,
                'updated_at' => $nowMs,
            ]],
            ['match_id', 'player_id', 'weapon_bit'],
            ['hits', 'atts', 'kills', 'deaths', 'headshots', 'accuracy_bp', 'recorded_at', 'updated_at']
        );
        $counters['weapons_written']++;
    }

    // Aggregated match stats
    $player = DB::table('tracker_players')->where('id', $playerId)->first(['guid_hash']);
    $guidHash = $player?->guid_hash ?? '';

    $tK = 0; $tD = 0; $tH = 0; $tHi = 0; $tA = 0;
    foreach ($parsed['weapons'] as $w) {
        $tK += $w['kills']; $tD += $w['deaths']; $tH += $w['headshots'];
        $tHi += $w['hits']; $tA += $w['atts'];
    }
    $accuracyPct = $tA > 0 ? round($tHi * 100 / $tA, 2) : 0.0;

    $damage = $parsed['damage'] ?? [];
    $client = $parsed['client'] ?? [];

    $skillRating = null; $skillDelta = null; $prestige = null;
    if (count($parsed['tail']) >= 3) {
        $r = $parsed['tail'][0]; $rd = $parsed['tail'][1]; $pr = $parsed['tail'][2];
        if (is_numeric($r) && is_numeric($rd)) {
            $skillRating = (float) $r; $skillDelta = (float) $rd;
        }
        if (is_numeric($pr) && !str_contains($pr, '.')) $prestige = (int) $pr;
    }

    $rawSkills = json_encode([
        'mask' => $parsed['skill_mask'],
        'mode' => $parsed['skill_mode'] ?? 'single',
        'skills' => $parsed['skills'],
    ]);

    DB::table('tracker_player_match_stats')->upsert(
        [[
            'match_id' => $match->id,
            'server_id' => $serverId,
            'player_id' => $playerId,
            'guid_hash' => $guidHash,
            'slot' => $parsed['slot'],
            'class' => $client['class'] ?? 0,
            'name_snapshot' => $client['name'] ?? '',
            'name_clean_snapshot' => preg_replace('/\^[0-9a-zA-Z]/', '', $client['name'] ?? ''),
            'kills' => $tK,
            'deaths' => $tD,
            'headshots' => $tH,
            'accuracy_pct' => $accuracyPct,
            'weapon_bitmask' => $parsed['weapon_mask'],
            'damage_given' => $damage['given'] ?? 0,
            'damage_received' => $damage['received'] ?? 0,
            'team_damage_given' => $damage['team_given'] ?? 0,
            'team_damage_received' => $damage['team_received'] ?? 0,
            'team_kills' => $damage['team_kills'] ?? 0,
            'gibs' => $damage['gibs'] ?? 0,
            'kill_assists' => $damage['kill_assists'] ?? 0,
            'self_kills' => $damage['self_kills'] ?? 0,
            'team_gibs' => $damage['team_gibs'] ?? 0,
            'time_played_pct' => $parsed['time_played_pct'],
            'ping_avg' => $client['ping'] ?? 0,
            'score' => $client['score'] ?? 0,
            'skill_rating' => $skillRating,
            'skill_rating_delta' => $skillDelta,
            'prestige' => $prestige,
            'raw_skills' => $rawSkills,
            'created_at' => $nowMs,
            'updated_at' => $nowMs,
        ]],
        ['match_id', 'slot'],
        [
            'player_id', 'class', 'name_snapshot', 'name_clean_snapshot',
            'kills', 'deaths', 'headshots', 'accuracy_pct', 'weapon_bitmask',
            'damage_given', 'damage_received', 'team_damage_given', 'team_damage_received',
            'team_kills', 'gibs', 'kill_assists', 'self_kills', 'team_gibs',
            'time_played_pct', 'ping_avg', 'score',
            'skill_rating', 'skill_rating_delta', 'prestige', 'raw_skills',
            'updated_at',
        ]
    );
    $counters['stats_written']++;
}

echo "=== Result ===\n";
foreach ($counters as $k => $v) echo "  " . str_pad($k, 20) . ": $v\n";

echo "\n=== Sample after ===\n";
$samples = DB::table('tracker_player_match_stats as ms')
    ->join('tracker_matches as m', 'm.id', '=', 'ms.match_id')
    ->join('tracker_players as p', 'p.id', '=', 'ms.player_id')
    ->where('m.server_id', $serverId)
    ->where('ms.kills', '>', 0)
    ->orderByDesc('m.started_at')
    ->limit(10)
    ->get(['p.name_clean', 'm.map_name', 'ms.kills', 'ms.deaths', 'ms.headshots', 'ms.damage_given']);
if ($samples->isEmpty()) echo "  (keine Stats mit kills>0 gefunden)\n";
foreach ($samples as $s) {
    echo sprintf("  %-20s %-20s K=%d D=%d HS=%d DMG=%d\n",
        $s->name_clean, $s->map_name, $s->kills, $s->deaths, $s->headshots, $s->damage_given);
}
