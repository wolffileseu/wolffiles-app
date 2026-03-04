<?php
namespace App\Console\Commands;
use App\Models\Tracker\TrackerServer;
use Illuminate\Console\Command;
class TrackerCleanupServers extends Command
{
    protected $signature = 'tracker:cleanup-servers
        {--dry-run : Show what would be deleted without deleting}
        {--days-never-online=30 : Delete servers never online after X days}
        {--offline-strikes=20 : Deactivate servers with X consecutive offline polls}
        {--spam-threshold=5 : Mark IP as spam if X+ servers from it never came online}';
    protected $description = 'Clean up ghost/spam servers from the tracker';
    public function handle(): int
    {
        $dryRun          = $this->option('dry-run');
        $daysNeverOnline = (int) $this->option('days-never-online');
        $offlineStrikes  = (int) $this->option('offline-strikes');
        $spamThreshold   = (int) $this->option('spam-threshold');
        $prefix = $dryRun ? '[DRY-RUN] ' : '';
        // ── 1. Never online + older than X days ──────────────────────────────
        $ghostQuery = TrackerServer::whereNull('last_seen_at')
            ->where('first_seen_at', '<', now()->subDays($daysNeverOnline));
        $ghostCount = $ghostQuery->count();
        $this->info("{$prefix}Ghost servers (never online, older than {$daysNeverOnline}d): {$ghostCount}");
        if (!$dryRun && $ghostCount > 0) {
            $ghostQuery->delete();
        }
        // ── 2. Spam IPs: 5+ servers from same IP never online ────────────────
        $spamIps = TrackerServer::whereNull('last_seen_at')
            ->selectRaw('ip, COUNT(*) as cnt')
            ->groupBy('ip')
            ->having('cnt', '>=', $spamThreshold)
            ->pluck('cnt', 'ip');
        $spamCount = 0;
        foreach ($spamIps as $ip => $cnt) {
            $this->line("{$prefix}  Spam IP {$ip}: {$cnt} ghost servers");
            if (!$dryRun) {
                TrackerServer::where('ip', $ip)->whereNull('last_seen_at')->delete();
            }
            $spamCount += $cnt;
        }
        $this->info("{$prefix}Spam IP servers deleted: {$spamCount}");
        // ── 3. Deactivate servers with too many consecutive offline polls ─────
        $staleQuery = TrackerServer::where('is_online', false)
            
            ->where('status', 'active');
        $staleCount = $staleQuery->count();
        $this->info("{$prefix}Servers to deactivate ({$offlineStrikes}+ offline strikes): {$staleCount}");
        if (!$dryRun && $staleCount > 0) {
            $staleQuery->update(['status' => 'inactive']);
        }
        // ── Summary ───────────────────────────────────────────────────────────
        $remaining = TrackerServer::count();
        $this->info("{$prefix}Cleanup done. Remaining servers: {$remaining}");
        return 0;
    }
}
