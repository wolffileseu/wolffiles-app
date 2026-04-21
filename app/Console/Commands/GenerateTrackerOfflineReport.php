<?php

namespace App\Console\Commands;

use App\Models\Tracker\TrackerServer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Writes a plain-text report of all offline tracker servers to storage,
 * split into "critical" (100+ failures, likely permanently gone)
 * and "currently offline" (3-99 failures, likely temporarily down).
 */
class GenerateTrackerOfflineReport extends Command
{
    protected $signature = 'tracker:offline-report';
    protected $description = 'Generate plain-text report of offline tracker servers';

    public function handle(): int
    {
        $total = TrackerServer::where('status', 'active')->count();

        $offline = TrackerServer::where('status', 'active')
            ->where('is_online', false)
            ->where('poll_failures', '>=', 3)
            ->orderByDesc('poll_failures')
            ->get();

        $critical = $offline->where('poll_failures', '>=', 100);
        $current  = $offline->where('poll_failures', '<', 100);

        $lines = [];
        $lines[] = '=== Offline Tracker Servers ===';
        $lines[] = 'Generated: ' . now()->toDateTimeString();
        $lines[] = 'Offline: ' . $offline->count() . ' / ' . $total . ' servers';
        $lines[] = '';

        $lines[] = '=== Kritisch (100+ fehlgeschlagene Polls — wahrscheinlich dauerhaft offline) ===';
        $lines[] = 'Count: ' . $critical->count();
        $lines[] = '';
        foreach ($critical as $s) {
            $lines[] = $this->formatServer($s);
        }
        if ($critical->isEmpty()) {
            $lines[] = '(keine kritischen Server)';
            $lines[] = '';
        }

        $lines[] = '';
        $lines[] = '=== Aktuell offline (3-99 Failures — oft nur temporär) ===';
        $lines[] = 'Count: ' . $current->count();
        $lines[] = '';
        foreach ($current as $s) {
            $lines[] = $this->formatServer($s);
        }
        if ($current->isEmpty()) {
            $lines[] = '(keine)';
        }

        $content = implode("\n", $lines) . "\n";

        file_put_contents(public_path('tracker-offline-report.txt'), $content);

        $this->info('Report written: public/tracker-offline-report.txt');
        $this->info("  Critical: {$critical->count()}  |  Current: {$current->count()}  |  Total offline: {$offline->count()}");

        return self::SUCCESS;
    }

    private function formatServer(TrackerServer $s): string
    {
        $lastSeen = $s->last_seen_at
            ? $s->last_seen_at->toDateTimeString() . ' (' . $s->last_seen_at->diffForHumans() . ')'
            : 'nie';
        $host = $s->hostname_clean ?: $s->hostname ?: '(kein Hostname)';
        return sprintf(
            "#%-6d  %s\n    IP:        %s:%d\n    Game:      %s\n    Failures:  %d\n    Last seen: %s\n",
            $s->id,
            mb_strimwidth($host, 0, 60, '…'),
            $s->ip,
            $s->port,
            $s->game?->short_name ?? '?',
            $s->poll_failures,
            $lastSeen
        );
    }
}
