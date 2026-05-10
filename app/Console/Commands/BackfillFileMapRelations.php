<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillFileMapRelations extends Command
{
    protected $signature = 'files:backfill-map-relations
                            {--dry-run : Show matches without writing}
                            {--reset : Delete all auto relations before running (keeps manual)}
                            {--bot-id= : Only process a single bot file id (for testing)}';

    protected $description = 'Match bot/waypoint files to maps by filename token analysis.';

    /**
     * Generic installer / non-map-specific keywords. If a bot file matches one of
     * these patterns, it is skipped entirely (will not be linked to any map).
     */
    private array $skipPatterns = [
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

    /**
     * Tokens to strip from bot filenames before matching. Order matters
     * (longer first). All matched case-insensitively, word-boundary aware.
     */
    private array $stripTokens = [
        'botfiles', 'bot files', 'bot-files', 'bot_files',
        'waypoints', 'waypoint',
        'revised', 'final', 'beta', 'alpha', 'release',
        'bots', 'bot',
        'omnibot', 'omni-bot', 'omni bot',
        'fixed', 'updated', 'new',
    ];

    public function handle(): int
    {
        if ($this->option('reset') && !$this->option('dry-run')) {
            $deleted = DB::table('file_map_relations')->where('is_manual', false)->delete();
            $this->warn("Deleted {$deleted} auto relations (manual entries kept).");
        }

        // Load all maps with their clean name once
        $maps = File::where('category_id', 10)
            ->whereNotNull('map_name_clean')
            ->where('map_name_clean', '!=', '')
            ->get(['id', 'map_name_clean', 'file_name']);

        $this->info("Loaded {$maps->count()} maps with map_name_clean.");

        // Build lookup: normalized map_name_clean => collection of map files
        // (multiple maps can share map_name_clean, e.g. v1 + v101 of the same map)
        $mapIndex = [];
        foreach ($maps as $m) {
            $key = $this->normalize($m->map_name_clean);
            if (strlen($key) < 3) continue; // ignore too-short keys (false positives)
            $mapIndex[$key][] = $m;
        }

        // Sort keys by length desc - longer/more-specific matches win
        $sortedKeys = array_keys($mapIndex);
        usort($sortedKeys, fn($a, $b) => strlen($b) <=> strlen($a));
        $this->info('Indexed ' . count($sortedKeys) . ' unique map name tokens.');

        // Iterate bot files
        $query = File::where('category_id', 12);
        if ($botId = $this->option('bot-id')) {
            $query->where('id', $botId);
        }
        $bots = $query->get(['id', 'file_name', 'title']);

        $this->info("Processing {$bots->count()} bot files...");
        $bar = $this->output->createProgressBar($bots->count());
        $bar->start();

        $stats = ['matched' => 0, 'skipped' => 0, 'no_match' => 0, 'multi_match' => 0, 'inserted' => 0];
        $samples = [];

        foreach ($bots as $bot) {
            $bar->advance();
            $haystackRaw = trim(($bot->file_name ?? '') . ' ' . ($bot->title ?? ''));

            // Skip generic installers
            $skipMatch = null;
            foreach ($this->skipPatterns as $pattern) {
                if (preg_match($pattern, $haystackRaw)) { $skipMatch = $pattern; break; }
            }
            if ($skipMatch !== null) {
                $stats['skipped']++;
                continue;
            }

            // Strip tokens, normalize
            $cleaned = $haystackRaw;
            foreach ($this->stripTokens as $tok) {
                $cleaned = preg_replace('/\\b' . preg_quote($tok, '/') . '\\b/i', ' ', $cleaned);
            }
            $haystack = $this->normalize($cleaned);

            // Find all map keys that appear as a token in haystack
            $matches = [];
            foreach ($sortedKeys as $key) {
                if ($this->containsToken($haystack, $key)) {
                    $matches[$key] = $mapIndex[$key];
                }
            }

            if (empty($matches)) {
                $stats['no_match']++;
                if (count($samples) < 15) $samples[] = "NO MATCH: [{$bot->id}] {$haystackRaw}";
                continue;
            }

            // Determine confidence per match
            // Base 0.5, +0.3 if key length >= 6, +0.1 per extra char beyond 6 (cap 0.95)
            // -0.2 if multiple top-level keys matched (ambiguous)
            // Ambiguous = multiple DIFFERENT map name keys matched (e.g. "praetoria" AND "adlernest").
            // Versioned releases of the same map (same key, multiple files) are NOT ambiguous.
            $isAmbiguous = count($matches) > 1;

            // When ambiguous, prefer LONGEST matched key. Drop matches that are
            // substrings of a longer matched key (e.g. drop "antarctic" if "antarctic base" also matched).
            if ($isAmbiguous) {
                $keys = array_keys($matches);
                usort($keys, fn($a, $b) => strlen($b) <=> strlen($a));
                $kept = [];
                foreach ($keys as $k) {
                    $isSub = false;
                    foreach ($kept as $longer) {
                        if (str_contains(' ' . $longer . ' ', ' ' . $k . ' ')) { $isSub = true; break; }
                    }
                    if (!$isSub) $kept[] = $k;
                }
                $matches = array_intersect_key($matches, array_flip($kept));
                $isAmbiguous = count($matches) > 1;
            }
            if ($isAmbiguous) $stats['multi_match']++;

            foreach ($matches as $key => $mapFiles) {
                $confidence = 0.50;
                if (strlen($key) >= 6) $confidence += 0.30;
                $confidence += min(0.15, max(0, strlen($key) - 6) * 0.02);
                if ($isAmbiguous) $confidence -= 0.20;
                $confidence = max(0.20, min(0.95, $confidence));

                foreach ($mapFiles as $mapFile) {
                    if (!$this->option('dry-run')) {
                        // Skip if manual relation exists (never overwrite)
                        $existsManual = DB::table('file_map_relations')
                            ->where('map_file_id', $mapFile->id)
                            ->where('bot_file_id', $bot->id)
                            ->where('is_manual', true)
                            ->exists();
                        if ($existsManual) continue;

                        DB::table('file_map_relations')->updateOrInsert(
                            ['map_file_id' => $mapFile->id, 'bot_file_id' => $bot->id],
                            [
                                'relation_type' => 'bot_files',
                                'confidence' => $confidence,
                                'source' => 'auto',
                                'is_manual' => false,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                        $stats['inserted']++;
                    }
                }
            }
            $stats['matched']++;
            if (count($samples) < 15) {
                $first = array_key_first($matches);
                $samples[] = "MATCH (" . number_format($confidence, 2) . "): [{$bot->id}] {$haystackRaw} -> {$first}" . ($isAmbiguous ? ' [AMBIGUOUS x' . count($matches) . ']' : '');
            }
        }
        $bar->finish();
        $this->newLine(2);

        $this->table(['metric', 'count'], [
            ['bot files processed', $bots->count()],
            ['matched',             $stats['matched']],
            ['skipped (generic)',   $stats['skipped']],
            ['no match',            $stats['no_match']],
            ['multi-match (ambig)', $stats['multi_match']],
            ['rows written',        $stats['inserted']],
        ]);

        $this->info('Sample results:');
        foreach ($samples as $s) $this->line('  ' . $s);

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - no rows written. Use without --dry-run to apply.');
        }

        return self::SUCCESS;
    }

    /**
     * Normalize for token matching: lowercase, replace non-alphanum with space, collapse spaces.
     */
    private function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    /**
     * Check if needle appears as a whole token (or hyphen/underscore-separated chunk)
     * in haystack. Both should be pre-normalized.
     */
    private function containsToken(string $haystack, string $needle): bool
    {
        if ($needle === '' || $haystack === '') return false;
        $h = ' ' . $haystack . ' ';
        $n = ' ' . $needle . ' ';
        return str_contains($h, $n);
    }
}
