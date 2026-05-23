<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Trending alle 2 Stunden aktualisieren
Schedule::command('wolffiles:trending')->everyTwoHours();

// Achievements taeglich pruefen
Schedule::command('wolffiles:achievements')->daily();

// Map of the Week
Schedule::command('wolffiles:map-of-week')->weeklyOn(1, '08:00');

// Clear Temp
Schedule::command('cleanup:temp')->daily();

// ===== Tracker =====
// Discover new servers from master servers
Schedule::command('tracker:discover-servers')->everyFifteenMinutes()->withoutOverlapping();

// Poll all active servers
Schedule::command('tracker:poll-servers')->everyThirtySeconds()->withoutOverlapping();

// Cleanup ghost/spam servers daily
Schedule::command('tracker:health-check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('tracker:cleanup-servers --days-never-online=1')->dailyAt('03:00');
Schedule::command('tracker:cleanup-sessions')->everyTenMinutes()->withoutOverlapping();
Schedule::command('tracker:cleanup-matches')->hourly()->withoutOverlapping();
Schedule::command('tracker:calculate-elo')->dailyAt('02:30')->withoutOverlapping();

// Rankings: daily/weekly/monthly/alltime — runs after ELO is calculated
Schedule::command('tracker:calculate-rankings --period=daily')->dailyAt('02:45')->withoutOverlapping();
Schedule::command('tracker:calculate-rankings --period=weekly')->weeklyOn(1, '03:00')->withoutOverlapping();
Schedule::command('tracker:calculate-rankings --period=monthly')->monthlyOn(1, '03:15')->withoutOverlapping();
Schedule::command('tracker:calculate-rankings --period=alltime')->dailyAt('03:30')->withoutOverlapping();

// Sync maps with Wolffiles downloads
Schedule::command('tracker:sync-maps')->hourly();

// Fast Download
Schedule::command('fastdl:sync-maps')->hourly();
Schedule::command('fastdl:extract-pk3s --batch=50 --category=Maps')->hourly();


Schedule::command('analytics:fill-geoip --limit=50')->everyFiveMinutes();

// Server Hosting Lifecycle
use Illuminate\Support\Facades\Schedule;

Schedule::command('servers:send-reminders')->dailyAt('09:00');
Schedule::command('servers:suspend-expired')->dailyAt('00:15');
Schedule::command('servers:terminate-old')->dailyAt('01:00');
Schedule::command('servers:sync-status')->hourly();

// Omni-Bot: Pull from GitHub every 6 hours
Schedule::command('omnibot:sync --pull')->everySixHours();

// Rebuild server rankings every 10 minutes (materialized 30d avg_players snapshot)
Schedule::command('tracker:rebuild-rankings-30d')->everyThirtyMinutes()->withoutOverlapping()->runInBackground();

// Rebuild top-players snapshot every 30 minutes (very stable cumulative XP ranks)
Schedule::command('tracker:rebuild-top-players -q')->everyTenMinutes()->withoutOverlapping();

// Refresh offline-server text report
Schedule::command('tracker:offline-report -q')->everyTenMinutes()->withoutOverlapping();

// Refresh offline-server text report every 10 min
Schedule::command('tracker:offline-report -q')->everyTenMinutes()->withoutOverlapping();

// === BOT CLEANUP SAFETY-NET (Commit 6) ===
// Run daily cleanup as safety net in case new bot variants slip past the
// in-handler prevention (other bot software, custom names, etc).
Schedule::command('tracker:cleanup-bots --force')->dailyAt('04:15')->withoutOverlapping();

// =====================================================================
// PM (Direct Messages) retention -- Phase 8
// =====================================================================
// Purge message bodies and attachments older than retention_body_days
// (default 180 days). Conversations with active reports or evidence
// snapshots are skipped.
Schedule::command('pm:purge-retention')->dailyAt('03:45')->withoutOverlapping();

// =====================================================================
// Queue maintenance: prune failed_jobs older than 72h
// Prevents the table from growing unbounded after silent job failures
// (see incident 2026-05-23: 1.2M rows, 10.7 GB, full poll outage)
// =====================================================================
Schedule::command('queue:prune-failed --hours=72')->dailyAt('04:30')->withoutOverlapping();

Schedule::command('testserver:rotate-idle')->everyTwoHours()->withoutOverlapping();
