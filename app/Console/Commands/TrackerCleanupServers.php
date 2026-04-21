<?php
namespace App\Console\Commands;

use App\Models\Tracker\TrackerServer;
use Illuminate\Console\Command;

class TrackerCleanupServers extends Command
{
    protected $signature = 'tracker:cleanup-servers
        {--dry-run : Show what would be deleted without deleting}
        {--days-never-online=30 : Delete servers never online after X days}
        {--offline-strikes=50 : Deactivate servers with X+ consecutive failed polls (non-established)}
        {--established-offline-strikes=2000 : Deactivate established servers after this many failed polls (about 3-5 days of offline polls)}
        {--spam-threshold=5 : Mark IP as spam if X+ servers from it never came online}';
    protected $description = 'Clean up ghost/spam servers from the tracker (safely, respecting enhanced tracker + established servers)';

    public function handle(): int
    {
        $dryRun                  = $this->option('dry-run');
        $daysNeverOnline         = (int) $this->option('days-never-online');
        $offlineStrikes          = (int) $this->option('offline-strikes');
        $establishedStrikes      = (int) $this->option('established-offline-strikes');
        $spamThreshold           = (int) $this->option('spam-threshold');
        $prefix = $dryRun ? '[DRY-RUN] ' : '';

        // 1. Ghost servers: never online + older than X days
        $ghostQuery = TrackerServer::whereNull('last_seen_at')
            ->where('first_seen_at', '<', now()->subDays($daysNeverOnline))
            ->where('is_enhanced_tracker', false); // never delete enhanced-tracker servers automatically

        $ghostCount = $ghostQuery->count();
        $this->info("{$prefix}Ghost servers (never online, older than {$daysNeverOnline}d, not enhanced): {$ghostCount}");
        if (!$dryRun && $ghostCount > 0) {
            $ghostQuery->delete();
        }

        // 2. Spam IPs: many servers from same IP never online
        $spamIps = TrackerServer::whereNull('last_seen_at')
            ->where('is_enhanced_tracker', false)
            ->selectRaw('ip, COUNT(*) as cnt')
            ->groupBy('ip')
            ->having('cnt', '>=', $spamThreshold)
            ->pluck('cnt', 'ip');

        $spamCount = 0;
        foreach ($spamIps as $ip => $cnt) {
            $this->line("{$prefix}  Spam IP {$ip}: {$cnt} ghost servers");
            if (!$dryRun) {
                TrackerServer::where('ip', $ip)
                    ->whereNull('last_seen_at')
                    ->where('is_enhanced_tracker', false)
                    ->delete();
            }
            $spamCount += $cnt;
        }
        $this->info("{$prefix}Spam IP servers deleted: {$spamCount}");

        // 3. Deactivate servers with too many consecutive failed polls.
        //    - Established servers need FAR more strikes before being deactivated.
        //    - Enhanced-tracker servers with recent events are never deactivated.
        $staleQuery = TrackerServer::where('is_online', false)
            ->where('status', 'active')
            // not currently sending enhanced events
            ->where(function ($q) {
                $q->where('is_enhanced_tracker', false)
                  ->orWhere('enhanced_last_event_at', '<', now()->subHours(24))
                  ->orWhereNull('enhanced_last_event_at');
            })
            ->where(function ($q) use ($offlineStrikes, $establishedStrikes) {
                // Non-established (never online or < 1d old) -> normal threshold
                $q->where(function ($q2) use ($offlineStrikes) {
                    $q2->where(function ($q3) {
                        $q3->whereNull('last_seen_at')
                           ->orWhere('first_seen_at', '>', now()->subDay());
                    })
                    ->where('poll_failures', '>=', $offlineStrikes);
                })
                // Established -> much higher threshold (real outage, not a blip)
                ->orWhere(function ($q2) use ($establishedStrikes) {
                    $q2->whereNotNull('last_seen_at')
                       ->where('first_seen_at', '<=', now()->subDay())
                       ->where('poll_failures', '>=', $establishedStrikes);
                });
            });

        $staleCount = $staleQuery->count();
        $this->info("{$prefix}Servers to deactivate (failed-poll thresholds reached): {$staleCount}");
        if (!$dryRun && $staleCount > 0) {
            $staleQuery->update(['status' => 'inactive']);
        }

        // Summary
        $remaining = TrackerServer::count();
        $active    = TrackerServer::where('status', 'active')->count();
        $inactive  = TrackerServer::where('status', 'inactive')->count();
        $this->info("{$prefix}Cleanup done. Total: {$remaining} | Active: {$active} | Inactive: {$inactive}");
        return 0;
    }
}
