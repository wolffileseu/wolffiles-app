<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Stale Session Cleanup.
 *
 * Sessions are opened via PlayerPresenceHandler on 'connect' and closed on
 * 'disconnect'. When a server crashes or is ungracefully killed, disconnects
 * are never sent — sessions stay open forever.
 *
 * Strategy:
 *   1. Any session older than --max-hours (default 8h) without an end_at gets
 *      closed. ET matches rarely last that long; real long-sitters are
 *      spectator-AFK and aren't losing meaningful stats by being closed.
 *   2. Additionally: sessions on servers that went offline (no poll in 30min)
 *      also get closed, regardless of age.
 *
 * duration_minutes is computed from started_at → ended_at.
 */
class TrackerCleanupSessions extends Command
{
    protected $signature = 'tracker:cleanup-sessions
        {--dry-run : Show what would be closed without closing}
        {--max-hours=8 : Max session age before forced close}
        {--server-offline-minutes=30 : Close sessions on servers that stopped polling}';

    protected $description = 'Close orphaned tracker_player_sessions from crashed/offline servers';

    public function handle(): int
    {
        $dryRun       = $this->option('dry-run');
        $maxHours     = (int) $this->option('max-hours');
        $offlineMin   = (int) $this->option('server-offline-minutes');
        $prefix       = $dryRun ? '[DRY-RUN] ' : '';
        $now          = Carbon::now();

        // === Category A: age-based ===
        $ageCutoff    = $now->copy()->subHours($maxHours);
        $ageQuery     = DB::table('tracker_player_sessions')
            ->whereNull('ended_at')
            ->where('started_at', '<', $ageCutoff);
        $ageCount     = $ageQuery->count();
        $this->info("{$prefix}Sessions older than {$maxHours}h: {$ageCount}");

        // === Category B: server went offline ===
        $offlineCutoff = $now->copy()->subMinutes($offlineMin);
        $offlineServerIds = DB::table('tracker_servers')
            ->where(function ($q) use ($offlineCutoff) {
                $q->whereNull('last_seen_at')
                  ->orWhere('last_seen_at', '<', $offlineCutoff);
            })
            ->pluck('id');

        $offlineQuery = DB::table('tracker_player_sessions')
            ->whereNull('ended_at')
            ->whereIn('server_id', $offlineServerIds);
        $offlineCount = $offlineQuery->count();
        $this->info("{$prefix}Sessions on servers offline >{$offlineMin}min: {$offlineCount}");

        if ($dryRun) {
            $this->comment('Dry run — nothing changed.');
            return self::SUCCESS;
        }

        // Close both categories. duration_minutes = minutes between started_at and now.
        // We use MIN(NOW(), started_at + max_hours) so age-based sessions don't get
        // 8+ hour durations (we assume they crashed at ~max-hours mark).
        $maxDurationMin = $maxHours * 60;
        $closedAge = DB::update(
            "UPDATE tracker_player_sessions
             SET ended_at = ?,
                 duration_minutes = LEAST(?, TIMESTAMPDIFF(MINUTE, started_at, ?))
             WHERE ended_at IS NULL
               AND started_at < ?",
            [$now, $maxDurationMin, $now, $ageCutoff]
        );
        $this->info("✓ Closed {$closedAge} stale (age) sessions");

        $closedOffline = 0;
        if ($offlineServerIds->isNotEmpty()) {
            $closedOffline = DB::table('tracker_player_sessions')
                ->whereNull('ended_at')
                ->whereIn('server_id', $offlineServerIds)
                ->update([
                    'ended_at' => $now,
                    'duration_minutes' => DB::raw('TIMESTAMPDIFF(MINUTE, started_at, NOW())'),
                ]);
        }
        $this->info("✓ Closed {$closedOffline} sessions on offline servers");

        return self::SUCCESS;
    }
}
