<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Stale Match Cleanup.
 *
 * Matches are opened on 'map' event and closed on 'mapend'/'maprestart'.
 * If a server crashes mid-match, the open match row stays open forever.
 *
 * Strategy: any open match older than --max-hours (default 3h) gets closed
 * with end_reason='timeout'. A real ET match is typically 15-40min —
 * 3 hours is enormous headroom even for tournament-length matches.
 */
class TrackerCleanupMatches extends Command
{
    protected $signature = 'tracker:cleanup-matches
        {--dry-run : Show what would be closed without closing}
        {--max-hours=3 : Max match age before forced close}';

    protected $description = 'Close orphaned tracker_matches rows from crashed servers';

    public function handle(): int
    {
        $dryRun   = $this->option('dry-run');
        $maxHours = (int) $this->option('max-hours');
        $prefix   = $dryRun ? '[DRY-RUN] ' : '';
        $now      = Carbon::now();
        $cutoff   = $now->copy()->subHours($maxHours);

        $query = DB::table('tracker_matches')
            ->whereNull('ended_at')
            ->where('started_at', '<', $cutoff);

        $count = $query->count();
        $this->info("{$prefix}Open matches older than {$maxHours}h: {$count}");

        if ($dryRun || $count === 0) {
            if ($dryRun) $this->comment('Dry run — nothing changed.');
            return self::SUCCESS;
        }

        $closed = $query->update([
            'ended_at'         => $now->format('Y-m-d H:i:s.v'),
            'end_reason'       => 'timeout',
            'duration_seconds' => DB::raw('TIMESTAMPDIFF(SECOND, started_at, NOW())'),
            'updated_at'       => $now,
        ]);

        $this->info("✓ Closed {$closed} stale matches");
        return self::SUCCESS;
    }
}
