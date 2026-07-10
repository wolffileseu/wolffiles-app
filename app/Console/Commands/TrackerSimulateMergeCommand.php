<?php

namespace App\Console\Commands;

use Throwable;
use App\Jobs\ProcessTrackerEventJob;
use App\Services\Tracker\PollerHashService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * tracker:simulate-merge
 *
 * Shows what would happen if we re-processed all raw tracker events with the
 * current handler code. Three modes:
 *
 *   php artisan tracker:simulate-merge
 *     → Dry-run. Reports planned actions. Zero DB writes.
 *
 *   php artisan tracker:simulate-merge --rollback
 *     → Runs the real code inside a transaction, shows post-state,
 *       then ROLLBACK. DB is unchanged at end.
 *
 *   php artisan tracker:simulate-merge --commit
 *     → Actually performs the changes. Irreversible. Requires confirmation.
 */
class TrackerSimulateMergeCommand extends Command
{
    protected $signature = 'tracker:simulate-merge
        {--rollback : Execute real code in a transaction, then rollback}
        {--commit : Actually perform the changes (requires confirmation)}';

    protected $description = 'Simulate or perform the Enhanced Tracker merge/reprocess';

    public function handle(): int
    {
        $rollback = (bool) $this->option('rollback');
        $commit = (bool) $this->option('commit');

        if ($rollback && $commit) {
            $this->error('Cannot use --rollback and --commit together.');
            return Command::FAILURE;
        }

        $mode = match (true) {
            $commit   => 'COMMIT',
            $rollback => 'ROLLBACK-TEST',
            default   => 'DRY-RUN',
        };

        $this->line('');
        $this->line('=== TRACKER MERGE SIMULATION ===');
        $this->line("Mode: {$mode}");
        $this->line('');

        if ($commit && !$this->confirm('This will PERMANENTLY modify tracker_players, tracker_matches, and tracker_player_match_stats. Continue?', false)) {
            $this->info('Aborted.');
            return Command::SUCCESS;
        }

        // Step 1: analyze what's in raw_events
        $this->showRawEventsSummary();

        // Step 2: plan the merges
        $plan = $this->buildMergePlan();
        $this->showMergePlan($plan);

        // Step 3: plan the cleanups (legacy enhanced-only rows, bad matches)
        $cleanup = $this->buildCleanupPlan();
        $this->showCleanupPlan($cleanup);

        // Step 4: execute or not
        if ($mode === 'DRY-RUN') {
            $this->line('');
            $this->info('✓ Dry-run complete. No changes made.');
            $this->line('Run with --rollback to test the real code with automatic undo.');
            $this->line('Run with --commit to actually perform the changes.');
            return Command::SUCCESS;
        }

        return $this->runExecution($plan, $cleanup, $rollback);
    }

    // ----------------------------------------------------------------------
    // Analysis
    // ----------------------------------------------------------------------

    private function showRawEventsSummary(): void
    {
        $this->line('[1/4] Raw events in buffer');

        $byCmd = DB::table('tracker_raw_events')
            ->select('cmd', DB::raw('count(*) as cnt'), DB::raw('sum(processed) as processed_cnt'))
            ->groupBy('cmd')
            ->orderByDesc('cnt')
            ->get();

        foreach ($byCmd as $r) {
            $this->line(sprintf('  %-15s %5d total | %5d processed', $r->cmd, $r->cnt, $r->processed_cnt));
        }
        $this->line('');
    }

    private function buildMergePlan(): array
    {
        $this->line('[2/4] Connect events — matching plan');

        $hasher = new PollerHashService();
        $plan = [
            'stage1_hits' => [],   // already linked via real_guid_hash
            'stage2_hits' => [],   // matchable via Poller name-hash
            'stage3_creates' => [], // no match → create
        ];

        // Look at UNIQUE (guid, name_clean) combinations from connect events
        $connects = DB::table('tracker_raw_events')
            ->where('cmd', 'connect')
            ->pluck('payload')
            ->unique();

        $seenGuids = [];   // dedupe by real_guid_hash
        foreach ($connects as $payload) {
            if (!preg_match('/^connect\s+(\d+)\s+(\S+)\s+(.*)$/s', $payload, $m)) {
                continue;
            }
            $realGuid = $m[2];
            $name = rtrim($m[3]);
            $nameClean = $hasher->stripColorCodes($name);
            $realHash = $hasher->hashFromRealGuid($realGuid);
            $pollerHash = $hasher->hashFromName($name);

            if (isset($seenGuids[$realHash])) {
                continue;
            }
            $seenGuids[$realHash] = true;

            // Stage 1: already linked?
            $linked = DB::table('tracker_players')
                ->where('real_guid_hash', $realHash)
                ->first(['id', 'name_clean', 'has_enhanced_data']);
            if ($linked) {
                $plan['stage1_hits'][] = compact('realGuid', 'name', 'nameClean', 'realHash') + [
                    'player_id' => $linked->id,
                    'player_name' => $linked->name_clean,
                ];
                continue;
            }

            // Stage 2: Poller-hash match?
            $match = DB::table('tracker_players')
                ->where('guid_hash', $pollerHash)
                ->whereNull('real_guid_hash')
                ->first(['id', 'name_clean', 'first_seen_at', 'total_sessions']);
            if ($match) {
                $plan['stage2_hits'][] = compact('realGuid', 'name', 'nameClean', 'realHash', 'pollerHash') + [
                    'player_id' => $match->id,
                    'player_name' => $match->name_clean,
                    'first_seen_at' => $match->first_seen_at,
                    'total_sessions' => $match->total_sessions,
                ];
                continue;
            }

            // Stage 3: would create
            $plan['stage3_creates'][] = compact('realGuid', 'name', 'nameClean', 'realHash', 'pollerHash');
        }

        return $plan;
    }

    private function showMergePlan(array $plan): void
    {
        $this->line('');
        $this->line(sprintf('  Stage 1 (already linked): %d', count($plan['stage1_hits'])));
        foreach ($plan['stage1_hits'] as $p) {
            $this->line(sprintf('    → player %d (%s): already has real_guid_hash, will just bump timestamps', $p['player_id'], $p['player_name']));
        }

        $this->line(sprintf('  Stage 2 (Poller-hash match): %d', count($plan['stage2_hits'])));
        foreach ($plan['stage2_hits'] as $p) {
            $this->line(sprintf('    → player %d (%s, %d sessions, since %s)',
                $p['player_id'], $p['player_name'], $p['total_sessions'], $p['first_seen_at']));
            $this->line(sprintf('      will set real_guid_hash=%s..., has_enhanced_data=true',
                substr($p['realHash'], 0, 12)));
        }

        $this->line(sprintf('  Stage 3 (new players): %d', count($plan['stage3_creates'])));
        foreach ($plan['stage3_creates'] as $p) {
            $this->line(sprintf('    → CREATE for "%s" (bot: %s)',
                $p['nameClean'], preg_match('/^\s*\[BOT\]/i', $p['nameClean']) ? 'yes' : 'no'));
        }
        $this->line('');
    }

    private function buildCleanupPlan(): array
    {
        $this->line('[3/4] Cleanup plan (legacy bad data)');

        // Enhanced-only rows that were created by the OLD (buggy) handler —
        // they have real_guid_hash = null AND has_enhanced_data = true AND
        // name/name_clean = '' (Enhanced stub with no real link).
        $legacyStubPlayers = DB::table('tracker_players')
            ->where('has_enhanced_data', true)
            ->whereNull('real_guid_hash')
            ->where(function ($q) {
                $q->where('name', '')->orWhereNull('name');
            })
            ->get(['id', 'guid_hash', 'created_at']);

        // Matches with invalid duration (ended_at < started_at because of
        // millisecond truncation bug in the first deploy).
        $badMatches = DB::table('tracker_matches')
            ->whereNotNull('ended_at')
            ->whereRaw('ended_at <= started_at')
            ->get(['id', 'map_name', 'started_at', 'ended_at', 'end_reason']);

        // Matches without ended_at that are older than 6 hours — orphaned
        // from before we had proper lifecycle handling.
        $orphanedOpenMatches = DB::table('tracker_matches')
            ->whereNull('ended_at')
            ->where('started_at', '<', now()->subHours(6))
            ->get(['id', 'server_id', 'map_name', 'started_at']);

        return compact('legacyStubPlayers', 'badMatches', 'orphanedOpenMatches');
    }

    private function showCleanupPlan(array $cleanup): void
    {
        $this->line(sprintf('  Legacy Enhanced-stub players (empty name, no real_guid_hash): %d',
            count($cleanup['legacyStubPlayers'])));
        foreach ($cleanup['legacyStubPlayers'] as $p) {
            $this->line(sprintf('    → DELETE player %d (created %s)', $p->id, $p->created_at));
        }

        $this->line(sprintf('  Matches with invalid duration (ended_at <= started_at): %d',
            count($cleanup['badMatches'])));
        foreach ($cleanup['badMatches'] as $m) {
            $this->line(sprintf('    → DELETE match %d: %s %s → %s (%s)',
                $m->id, $m->map_name, $m->started_at, $m->ended_at, $m->end_reason));
        }

        $this->line(sprintf('  Orphaned open matches (no end, >6h old): %d',
            count($cleanup['orphanedOpenMatches'])));
        foreach ($cleanup['orphanedOpenMatches'] as $m) {
            $this->line(sprintf('    → DELETE match %d: server %d %s since %s',
                $m->id, $m->server_id, $m->map_name, $m->started_at));
        }
        $this->line('');
    }

    // ----------------------------------------------------------------------
    // Execution
    // ----------------------------------------------------------------------

    private function runExecution(array $plan, array $cleanup, bool $rollback): int
    {
        $this->line('[4/4] Executing...');

        try {
            DB::beginTransaction();

            // Step A: Cleanup legacy data
            $deletedPlayers = 0;
            foreach ($cleanup['legacyStubPlayers'] as $p) {
                DB::table('tracker_players')->where('id', $p->id)->delete();
                $deletedPlayers++;
            }
            $this->line(sprintf('  ✓ Deleted %d legacy stub players', $deletedPlayers));

            $deletedMatches = 0;
            foreach ($cleanup['badMatches'] as $m) {
                DB::table('tracker_matches')->where('id', $m->id)->delete();
                $deletedMatches++;
            }
            foreach ($cleanup['orphanedOpenMatches'] as $m) {
                DB::table('tracker_matches')->where('id', $m->id)->delete();
                $deletedMatches++;
            }
            $this->line(sprintf('  ✓ Deleted %d bad/orphaned matches', $deletedMatches));

            // Step B: Reset processed flag on all raw events so reprocessing picks them up
            $reset = DB::table('tracker_raw_events')
                ->update([
                    'processed' => false,
                    'processed_at' => null,
                    'processing_error' => null,
                ]);
            $this->line(sprintf('  ✓ Reset %d raw events to processed=false', $reset));

            // Step C: Dispatch reprocessing jobs (into the queue)
            // We inline-dispatch-sync here so we can roll back if needed.
            $eventIds = DB::table('tracker_raw_events')
                ->orderBy('received_at')
                ->pluck('id');

            $bar = $this->output->createProgressBar(count($eventIds));
            $bar->start();
            foreach ($eventIds as $eventId) {
                (new ProcessTrackerEventJob($eventId))->handle();
                $bar->advance();
            }
            $bar->finish();
            $this->line('');

            // Step D: Show the post-state
            $this->showPostState();

            if ($rollback) {
                DB::rollBack();
                $this->warn('');
                $this->warn('✓ ROLLBACK applied. DB is back to its pre-simulation state.');
                $this->line('If the post-state looks correct, re-run with --commit to make it permanent.');
            } else {
                DB::commit();
                $this->info('');
                $this->info('✓ COMMIT applied. Changes are permanent.');
            }
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Execution failed, rolling back:');
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function showPostState(): void
    {
        $this->line('');
        $this->line('--- POST-STATE ---');

        $enhancedServers = DB::table('tracker_servers')->where('is_enhanced_tracker', true)->count();
        $enhancedPlayers = DB::table('tracker_players')->where('has_enhanced_data', true)->count();
        $linkedPlayers = DB::table('tracker_players')->whereNotNull('real_guid_hash')->count();
        $botPlayers = DB::table('tracker_players')->where('is_bot', true)->count();
        $matches = DB::table('tracker_matches')->count();
        $openMatches = DB::table('tracker_matches')->whereNull('ended_at')->count();
        $rawEvents = DB::table('tracker_raw_events')->where('processed', true)->count();

        $this->line(sprintf('  Enhanced servers: %d', $enhancedServers));
        $this->line(sprintf('  Enhanced players: %d', $enhancedPlayers));
        $this->line(sprintf('  Linked players (have real_guid_hash): %d', $linkedPlayers));
        $this->line(sprintf('  Bot players: %d', $botPlayers));
        $this->line(sprintf('  Matches: %d (of which %d still open)', $matches, $openMatches));
        $this->line(sprintf('  Processed raw events: %d', $rawEvents));
    }
}
