<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\Tracker\PollerHashService;

$serverId = (int) ($argv[1] ?? 26510);
$hasher = new PollerHashService();

echo "=== Slot-Rebuild für Server {$serverId} ===" . PHP_EOL;

// Kaputte Rows sind schon gelöscht durch den vorigen Run — nochmal prüfen
$before = DB::table('tracker_server_slots')->where('server_id', $serverId)->count();
echo "Current slot rows: {$before}" . PHP_EOL;

$connects = DB::table('tracker_raw_events')
    ->where('server_id', $serverId)
    ->where('cmd', 'connect')
    ->orderBy('received_at')
    ->get(['id', 'received_at', 'payload']);

echo "Zu verarbeiten: " . count($connects) . " connect-events" . PHP_EOL . PHP_EOL;

$inserted = 0;
$skipBots = 0;
$skipParseErr = 0;
$linkedPoller = 0;
$linkedReal = 0;

foreach ($connects as $e) {
    if (!preg_match('/^connect\s+(\d+)\s+(\S+)\s+(.*)$/s', $e->payload, $m)) {
        $skipParseErr++;
        continue;
    }
    $slot = (int) $m[1];
    $guid = $m[2];
    $name = rtrim($m[3]);

    // Bot-Filter
    if (preg_match('/^0+BOT\d+$/i', $guid) || str_contains(strtolower($name), '[bot]')) {
        $skipBots++;
        continue;
    }

    // Korrekter Hash-Algorithmus (wie PollerHashService)
    $realHash = $hasher->hashFromRealGuid($guid);
    $nameHash = $hasher->hashFromName($name);

    // Stage 1: bereits verknüpfter Player
    $player = DB::table('tracker_players')
        ->where('real_guid_hash', $realHash)
        ->first(['id']);
    if ($player) {
        $playerId = (int) $player->id;
        $linkedReal++;
    } else {
        // Stage 2: Poller-Player mit gleichem Namens-Hash ohne Real-Link
        $player = DB::table('tracker_players')
            ->where('guid_hash', $nameHash)
            ->whereNull('real_guid_hash')
            ->first(['id']);
        if ($player) {
            $playerId = (int) $player->id;
            // Verknüpfen für die Zukunft (wie der Handler es täte)
            DB::table('tracker_players')->where('id', $playerId)->update([
                'real_guid_hash' => $realHash,
                'has_enhanced_data' => true,
                'enhanced_last_seen_at' => $e->received_at,
                'updated_at' => $e->received_at,
            ]);
            $linkedPoller++;
        } else {
            // Nicht bekannt — überspringen
            continue;
        }
    }

    // disconnect finden (nächste explizite disconnect ODER nächster connect auf selbem Slot)
    $nextDisc = DB::table('tracker_raw_events')
        ->where('server_id', $serverId)
        ->where('cmd', 'disconnect')
        ->where('payload', 'LIKE', 'disconnect ' . $slot)
        ->where('received_at', '>', $e->received_at)
        ->orderBy('received_at')
        ->first(['received_at']);

    $nextConn = DB::table('tracker_raw_events')
        ->where('server_id', $serverId)
        ->where('cmd', 'connect')
        ->where('id', '>', $e->id)
        ->where('payload', 'LIKE', 'connect ' . $slot . ' %')
        ->orderBy('received_at')
        ->first(['received_at']);

    $discAt = null;
    if ($nextDisc && $nextConn) {
        $discAt = ($nextDisc->received_at < $nextConn->received_at)
            ? $nextDisc->received_at
            : $nextConn->received_at;
    } elseif ($nextDisc) {
        $discAt = $nextDisc->received_at;
    } elseif ($nextConn) {
        $discAt = $nextConn->received_at;
    }

    DB::table('tracker_server_slots')->insert([
        'server_id' => $serverId,
        'slot' => $slot,
        'player_id' => $playerId,
        'connected_at' => $e->received_at,
        'disconnected_at' => $discAt,
        'created_at' => $e->received_at,
        'updated_at' => $discAt ?? $e->received_at,
    ]);
    $inserted++;
}

echo "Rekonstruiert: {$inserted}" . PHP_EOL;
echo "  davon via real_guid_hash (Stage 1): {$linkedReal}" . PHP_EOL;
echo "  davon via name-hash (Stage 2, neu verknüpft): {$linkedPoller}" . PHP_EOL;
echo "Skipped (bot): {$skipBots}" . PHP_EOL;
echo "Skipped (parse error): {$skipParseErr}" . PHP_EOL;

echo PHP_EOL . "=== Sample reconstructed slots ===" . PHP_EOL;
$samples = DB::table('tracker_server_slots as s')
    ->leftJoin('tracker_players as p', 'p.id', '=', 's.player_id')
    ->where('s.server_id', $serverId)
    ->orderByDesc('s.id')
    ->limit(10)
    ->get(['s.*', 'p.name_clean']);
foreach ($samples as $s) {
    $dur = '';
    if ($s->disconnected_at) {
        $secs = strtotime($s->disconnected_at) - strtotime($s->connected_at);
        $dur = ' (' . gmdate('H:i:s', max(0, $secs)) . ')';
    }
    echo "  slot={$s->slot} "
        . str_pad((string) $s->player_id, 6)
        . " " . str_pad($s->name_clean ?? '?', 20)
        . " conn={$s->connected_at}"
        . " disc=" . ($s->disconnected_at ?? 'OPEN')
        . $dur . PHP_EOL;
}
