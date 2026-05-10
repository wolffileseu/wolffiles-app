<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\DB;

/**
 * Matches bot/waypoint files to maps by filename token analysis.
 * Used by both BackfillFileMapRelations command (bulk) and FileObserver (single file on save).
 */
class FileRelationMatcher
{
    public const CATEGORY_MAP = 10;
    public const CATEGORY_BOT = 12;

    /** Generic installer / non-map keywords - bot files matching these are skipped. */
    public array $skipPatterns = [
        '/^omni-?bot[ _-]/i',
        '/^etpub/i',
        '/^etpro/i',
        '/^silent[ _-]?mod/i',
        '/^nitmod/i',
        '/^jaymod/i',
        '/^etlegacy/i',
        '/waypoint[ _-]?pack/i',
        '/bot[ _-]?pack/i',
        '/all[ _-]?in[ _-]?one/i',
        '/^aio/i',
    ];

    /** Tokens stripped from bot filenames before matching (longer first). */
    public array $stripTokens = [
        'botfiles', 'bot files', 'bot-files', 'bot_files',
        'waypoints', 'waypoint',
        'revised', 'final', 'beta', 'alpha', 'release',
        'bots', 'bot',
        'omnibot', 'omni-bot', 'omni bot',
        'fixed', 'updated', 'new',
    ];

    /** Cache of map index across calls within the same request. */
    private ?array $mapIndexCache = null;
    private ?array $sortedKeysCache = null;

    /**
     * Match a single bot file against all maps. Writes relations to DB.
     * Returns array of inserted/updated map IDs.
     */
    public function matchBot(File $bot, bool $deleteOldAuto = true): array
    {
        if ((int) $bot->category_id !== self::CATEGORY_BOT) return [];

        $haystackRaw = trim(($bot->file_name ?? '') . ' ' . ($bot->title ?? ''));
        if ($haystackRaw === '') return [];

        // Generic installer skip
        foreach ($this->skipPatterns as $p) {
            if (preg_match($p, $haystackRaw)) return [];
        }

        $matches = $this->findMatches($haystackRaw);
        if (empty($matches)) return [];

        // Optionally clear stale auto relations for this bot (not manual ones)
        if ($deleteOldAuto) {
            DB::table('file_map_relations')
                ->where('bot_file_id', $bot->id)
                ->where('is_manual', false)
                ->delete();
        }

        $insertedMapIds = [];
        $isAmbiguous = count($matches) > 1;
        foreach ($matches as $key => $mapFiles) {
            $confidence = $this->scoreConfidence($key, $isAmbiguous);
            foreach ($mapFiles as $mapFile) {
                $this->upsertRelation((int)$mapFile->id, (int)$bot->id, $confidence);
                $insertedMapIds[] = (int) $mapFile->id;
            }
        }
        return array_values(array_unique($insertedMapIds));
    }

    /**
     * When a new map arrives, scan all existing bot files and link those that match.
     * Returns array of bot file IDs newly linked to this map.
     */
    public function matchMap(File $map): array
    {
        if ((int) $map->category_id !== self::CATEGORY_MAP) return [];
        if (empty($map->map_name_clean)) return [];

        $key = $this->normalize($map->map_name_clean);
        if (strlen($key) < 3) return [];

        $linkedBotIds = [];

        // Iterate all bot files, check if normalized haystack contains this map's key.
        // For 600-700 bots this is fast (~50-100ms).
        File::where('category_id', self::CATEGORY_BOT)
            ->select(['id', 'file_name', 'title'])
            ->chunk(200, function ($bots) use ($key, $map, &$linkedBotIds) {
                foreach ($bots as $bot) {
                    $haystackRaw = trim(($bot->file_name ?? '') . ' ' . ($bot->title ?? ''));
                    foreach ($this->skipPatterns as $p) {
                        if (preg_match($p, $haystackRaw)) continue 2;
                    }
                    $cleaned = $this->stripBotTokens($haystackRaw);
                    $haystack = $this->normalize($cleaned);
                    if (! $this->containsToken($haystack, $key)) continue;

                    $confidence = $this->scoreConfidence($key, false);
                    $this->upsertRelation((int)$map->id, (int)$bot->id, $confidence);
                    $linkedBotIds[] = (int) $bot->id;
                }
            });

        return $linkedBotIds;
    }

    /**
     * Find all map name keys that appear as tokens in the bot filename haystack.
     * Returns [key => [File, ...]] array, with substring-of-longer-key duplicates removed.
     */
    public function findMatches(string $haystackRaw): array
    {
        $cleaned = $this->stripBotTokens($haystackRaw);
        $haystack = $this->normalize($cleaned);
        if ($haystack === '') return [];

        [$mapIndex, $sortedKeys] = $this->getMapIndex();

        $matches = [];
        foreach ($sortedKeys as $key) {
            if ($this->containsToken($haystack, $key)) {
                $matches[$key] = $mapIndex[$key];
            }
        }

        // Drop substrings of longer matched keys
        if (count($matches) > 1) {
            $keys = array_keys($matches);
            usort($keys, fn($a, $b) => strlen($b) <=> strlen($a));
            $kept = [];
            foreach ($keys as $k) {
                $isSub = false;
                foreach ($kept as $longer) {
                    if (str_contains(' ' . $longer . ' ', ' ' . $k . ' ')) { $isSub = true; break; }
                }
                if (! $isSub) $kept[] = $k;
            }
            $matches = array_intersect_key($matches, array_flip($kept));
        }

        return $matches;
    }

    public function scoreConfidence(string $key, bool $isAmbiguous): float
    {
        $confidence = 0.50;
        if (strlen($key) >= 6) $confidence += 0.30;
        $confidence += min(0.15, max(0, strlen($key) - 6) * 0.02);
        if ($isAmbiguous) $confidence -= 0.20;
        return max(0.20, min(0.95, $confidence));
    }

    private function upsertRelation(int $mapId, int $botId, float $confidence): void
    {
        // Never overwrite manual links
        $existsManual = DB::table('file_map_relations')
            ->where('map_file_id', $mapId)
            ->where('bot_file_id', $botId)
            ->where('is_manual', true)
            ->exists();
        if ($existsManual) return;

        DB::table('file_map_relations')->updateOrInsert(
            ['map_file_id' => $mapId, 'bot_file_id' => $botId],
            [
                'relation_type' => 'bot_files',
                'confidence'    => $confidence,
                'source'        => 'auto',
                'is_manual'     => false,
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );
    }

    /**
     * Build (and cache for this request) the map name index.
     * @return array{0: array<string, array<int, File>>, 1: array<int, string>}
     */
    private function getMapIndex(): array
    {
        if ($this->mapIndexCache !== null && $this->sortedKeysCache !== null) {
            return [$this->mapIndexCache, $this->sortedKeysCache];
        }

        $maps = File::where('category_id', self::CATEGORY_MAP)
            ->whereNotNull('map_name_clean')
            ->where('map_name_clean', '!=', '')
            ->get(['id', 'map_name_clean', 'file_name']);

        $mapIndex = [];
        foreach ($maps as $m) {
            $key = $this->normalize($m->map_name_clean);
            if (strlen($key) < 3) continue;
            $mapIndex[$key][] = $m;
        }

        $sortedKeys = array_keys($mapIndex);
        usort($sortedKeys, fn($a, $b) => strlen($b) <=> strlen($a));

        $this->mapIndexCache = $mapIndex;
        $this->sortedKeysCache = $sortedKeys;

        return [$mapIndex, $sortedKeys];
    }

    public function clearCache(): void
    {
        $this->mapIndexCache = null;
        $this->sortedKeysCache = null;
    }

    private function stripBotTokens(string $s): string
    {
        foreach ($this->stripTokens as $tok) {
            $s = preg_replace('/\\b' . preg_quote($tok, '/') . '\\b/i', ' ', $s);
        }
        return $s;
    }

    public function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    public function containsToken(string $haystack, string $needle): bool
    {
        if ($needle === '' || $haystack === '') return false;
        return str_contains(' ' . $haystack . ' ', ' ' . $needle . ' ');
    }
}
