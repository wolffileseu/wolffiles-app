<?php

namespace App\Services\Tracker\Handlers;

use App\Models\Tracker\TrackerRawEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles name change events:
 *   - name <slot> <GUID> <new_name>
 *   e.g. "name 8 090CE9B8133614B8A2936053167FFD86 ^1wahke"
 *
 * Each name event adds or bumps a tracker_player_aliases row.
 *
 * IMPORTANT — Decision A:
 *   - tracker_player_aliases is shared between Server Poller and Enhanced Tracker
 *   - The existing 27k+ rows use player_id, name, name_clean, name_html, times_used
 *   - Enhanced additionally populates the new guid_hash column
 *   - We do NOT overwrite existing aliases — just increment times_used on collision
 */
class PlayerAliasHandler extends AbstractHandler
{
    public function supports(): array
    {
        return ['name'];
    }

    public function handle(TrackerRawEvent $event): void
    {
        $parsed = $this->parseName($event->payload);
        if ($parsed === null) {
            Log::warning('PlayerAliasHandler: malformed name packet', [
                'payload' => substr($event->payload, 0, 200),
            ]);
            return;
        }

        $guidHash = $this->hashGuid($parsed['guid']);

        // Find the player — may not exist if connect was never received
        // (unlikely but possible if the daemon was restarted mid-match).
        $player = DB::table('tracker_players')
            ->where('guid_hash', $guidHash)
            ->first(['id']);

        if ($player === null) {
            Log::info('PlayerAliasHandler: name event for unknown GUID, skipping', [
                'guid_hash_prefix' => substr($guidHash, 0, 8),
            ]);
            return;
        }

        $name = $parsed['name'];
        $nameClean = $this->stripColorCodes($name);
        $nameHtml = $this->renderNameHtml($name);

        // Upsert alias: increment times_used if (player_id, name) already exists.
        DB::transaction(function () use ($player, $guidHash, $name, $nameClean, $nameHtml, $event) {
            $existing = DB::table('tracker_player_aliases')
                ->where('player_id', $player->id)
                ->where('name', $name)
                ->first(['id', 'guid_hash']);

            if ($existing !== null) {
                $updates = ['last_seen_at' => $event->received_at];
                // Backfill guid_hash if missing on legacy rows
                if ($existing->guid_hash === null) {
                    $updates['guid_hash'] = $guidHash;
                }
                DB::table('tracker_player_aliases')
                    ->where('id', $existing->id)
                    ->update($updates);
                DB::table('tracker_player_aliases')
                    ->where('id', $existing->id)
                    ->increment('times_used');
            } else {
                DB::table('tracker_player_aliases')->insert([
                    'player_id' => $player->id,
                    'guid_hash' => $guidHash,
                    'name' => $name,
                    'name_clean' => $nameClean,
                    'name_html' => $nameHtml,
                    'times_used' => 1,
                    'first_seen_at' => $event->received_at,
                    'last_seen_at' => $event->received_at,
                ]);
            }
        });
    }

    /**
     * Parse a "name <slot> <guid> <new_name>" payload.
     *
     * @return array{slot:int, guid:string, name:string}|null
     */
    private function parseName(string $payload): ?array
    {
        if (!preg_match('/^name\s+(\d+)\s+([0-9A-Fa-f]{32})\s+(.*)$/s', $payload, $m)) {
            return null;
        }
        return [
            'slot' => (int) $m[1],
            'guid' => $m[2],
            'name' => rtrim($m[3]),
        ];
    }

    /**
     * Render a name with ET color codes as HTML, matching the existing
     * wolffiles convention (seen in tracker_player_aliases.name_html).
     *
     * Example: "I^6love^zJ^6:))"
     *   → 'I<span style="color:#FF00FF">love</span><span style="color:#3399FF">J</span><span style="color:#FF00FF">:))</span>'
     */
    private function renderNameHtml(string $name): string
    {
        static $colorMap = [
            '0' => '#000000',    '1' => '#FF0000',    '2' => '#00FF00',
            '3' => '#FFFF00',    '4' => '#0000FF',    '5' => '#00FFFF',
            '6' => '#FF00FF',    '7' => '#FFFFFF',    '8' => '#FF7F00',
            '9' => '#7F7F7F',    'z' => '#3399FF',
            // lowercase alphabetic codes mostly seen in the wild
            'o' => '#FF00FF',
        ];

        // Split the name at color-code boundaries
        $parts = preg_split('/(\^[0-9a-zA-Z])/', $name, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false || empty($parts)) {
            return htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        }

        $html = '';
        $currentColor = null;

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (strlen($part) === 2 && $part[0] === '^') {
                $code = strtolower($part[1]);
                $currentColor = $colorMap[$code] ?? '#FFFFFF';
                continue;
            }
            $escaped = htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
            if ($currentColor === null) {
                $html .= $escaped;   // leading text before any color code
            } else {
                $html .= sprintf('<span style="color:%s">%s</span>', $currentColor, $escaped);
            }
        }

        return $html;
    }
}
