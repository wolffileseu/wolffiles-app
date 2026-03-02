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
Schedule::command('tracker:poll-servers')->everyTwoMinutes()->withoutOverlapping();

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
