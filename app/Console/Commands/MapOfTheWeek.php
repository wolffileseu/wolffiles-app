<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\MotwHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SocialMedia\SocialMediaService;

class MapOfTheWeek extends Command
{
    protected $signature = 'wolffiles:map-of-week
        {--strategy=trending : Selection strategy: trending, downloads, rating, random}
        {--game=ET : Filter by game (ET, RtCW, or all)}
        {--dry-run : Show what would be selected without changing anything}';

    protected $description = 'Auto-rotate Map of the Week — selects a new featured file weekly';

    // Track recently featured files to avoid repeats (last 12 weeks)
    private const HISTORY_CACHE_KEY = 'motw_history';
    private const HISTORY_SIZE = 12;

    public function handle(): int
    {
        $strategy = $this->option('strategy');
        $game = $this->option('game');
        $dryRun = $this->option('dry-run');

        $this->info('===========================================');
        $this->info('  Map of the Week — Auto Rotation');
        $this->info('===========================================');
        $this->info("Strategy: {$strategy}");
        $this->info("Game: {$game}");

        // Get history of recently featured files (persistent DB-backed history)
        $history = MotwHistory::recentFileIds(self::HISTORY_SIZE);

        // Build query — only maps with screenshots
        $query = File::approved()
            ->whereHas('screenshots')
            ->whereNotIn('id', $history);

        // Filter by game
        if ($game !== 'all') {
            $query->where('game', $game);
        }

        // Only map categories (try to find maps, fall back to all files)
        $mapQuery = clone $query;
        $mapQuery->whereHas('category', function ($q) {
            $q->where('name', 'like', '%Map%');
        });

        // If we have maps, use them; otherwise fall back to all files
        if ($mapQuery->count() > 0) {
            $query = $mapQuery;
        }

        // Apply selection strategy
        $newFeatured = match ($strategy) {
            'trending' => $query->orderByDesc('trending_score')->orderByDesc('download_count')->first(),
            'downloads' => $query->orderByDesc('download_count')->first(),
            'rating' => $query->where('rating_count', '>', 0)->orderByDesc('average_rating')->orderByDesc('download_count')->first(),
            'random' => $query->where('download_count', '>', 0)->inRandomOrder()->first(),
            default => $query->orderByDesc('trending_score')->first(),
        };

        // Fallback: if no file found with strategy, pick random
        if (!$newFeatured) {
            $this->warn('No file found with strategy, trying random...');
            $newFeatured = File::approved()
                ->whereHas('screenshots')
                ->whereNotIn('id', $history)
                ->inRandomOrder()
                ->first();
        }

        if (!$newFeatured) {
            $this->error('No eligible file found for Map of the Week!');

            // Reset history if we've exhausted all options
            if (count($history) > 0) {
                $this->info('History exhausted, clearing for next attempt...');
                MotwHistory::truncate();
                return $this->handle();
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Selected: {$newFeatured->title}");
        $this->info("Game: {$newFeatured->game}");
        $this->info("Category: " . ($newFeatured->category->name ?? 'N/A'));
        $this->info("Downloads: {$newFeatured->download_count}");
        $this->info("Rating: " . ($newFeatured->average_rating ?? 'N/A'));
        $this->info("Trending: " . ($newFeatured->trending_score ?? 0));

        if ($dryRun) {
            $this->warn('DRY RUN — no changes made.');
            return self::SUCCESS;
        }

        // Issue #16: idempotent + atomic featured rotation.
        // Set the new featured row FIRST via Query Builder (bypasses Eloquent
        // dirty-tracking — protects against stale in-memory state if the model
        // was loaded with is_featured=true). Then clear all OTHER featured rows.
        // Wrapped in a transaction so the DB is never observed without a
        // featured file between the two writes.
        DB::transaction(function () use ($newFeatured) {
            File::whereKey($newFeatured->id)
                ->update(['is_featured' => true, 'featured_at' => now()]);

            File::where('is_featured', true)
                ->where('id', '!=', $newFeatured->id)
                ->update(['is_featured' => false]);
        });

        // Keep the in-memory model consistent with the DB for downstream uses.
        $newFeatured->is_featured = true;
        $newFeatured->featured_at = now();
        $newFeatured->syncOriginal();

        // Update history (persistent in DB — survives cache:clear)
        MotwHistory::record($newFeatured, $strategy, $game);

        $this->newLine();
        $this->info("✅ Map of the Week: {$newFeatured->title}");

        // Broadcast to all social media channels
        app(SocialMediaService::class)->broadcastMapOfTheWeek($newFeatured);

        Log::info("Map of the Week rotated", [
            'file_id' => $newFeatured->id,
            'title' => $newFeatured->title,
            'strategy' => $strategy,
        ]);

        return self::SUCCESS;
    }
}
