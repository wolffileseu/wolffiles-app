<?php
namespace App\Console\Commands;
use App\Models\Tracker\TrackerServer;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;

class TrackerHealthCheck extends Command
{
    protected $signature = 'tracker:health-check';
    protected $description = 'Check tracker health and notify via Telegram if something is wrong';
    public function handle(): int
    {
        $telegram = new TelegramNotificationService();
        $issues = [];
        $lastPoll = \App\Models\Tracker\TrackerServer::where('is_online', true)->max('last_poll_at');
        if (!$lastPoll || now()->diffInMinutes($lastPoll) > 5) {
            $issues[] = '⚠️ <b>Tracker Poll hängt</b> (>5min) — letzter Poll: ' . ($lastPoll ? $lastPoll : 'nie');
        }
        $total  = TrackerServer::where('status', 'active')->count();
        $online = TrackerServer::where('status', 'active')->where('is_online', true)->count();
        $ratio  = $total > 0 ? round($online / $total * 100) : 0;
        if ($ratio < 30 && $total > 10) {
            $issues[] = "⚠️ <b>Nur {$ratio}% der Server online</b> ({$online}/{$total}) — möglicher Poll-Ausfall";
        }
        $ghosts = TrackerServer::whereNull('last_seen_at')
            ->where('first_seen_at', '<', now()->subDays(1))
            ->count();
        if ($ghosts > 100) {
            $issues[] = "⚠️ <b>{$ghosts} Ghost-Server</b> entdeckt — Discovery läuft heiß";
        }
        // === Neuer Check: failed_jobs Spike (Incident 2026-05-23 prevention) ===
        $failedLastHour = \DB::table('failed_jobs')
            ->where('failed_at', '>=', now()->subHour())
            ->count();
        if ($failedLastHour > 1000) {
            $issues[] = " <b>{$failedLastHour} failed jobs in letzter Stunde</b> — Pipeline kaputt?";
        }

        // === Neuer Check: Poll-Coverage in letzten 5min ===
        $onlineCount = TrackerServer::where('is_online', true)->count();
        if ($onlineCount > 0) {
            $polledRecently = TrackerServer::where('is_online', true)
                ->where('last_poll_at', '>=', now()->subMinutes(5))
                ->count();
            $coverage = (int) round($polledRecently / $onlineCount * 100);
            if ($coverage < 30) {
                $issues[] = " <b>Nur {$coverage}% der online Server in 5min gepollt</b> ({$polledRecently}/{$onlineCount})";
            }
        }

        // Wenn Alert quittiert wurde, nicht senden
        if (!empty($issues) && \Illuminate\Support\Facades\Cache::get('tracker:alert_acked')) {
            $this->info('Alert acked, skipping notification.');
            return 0;
        }

        if (!empty($issues)) {
            $msg  = "🚨 <b>Wolffiles Tracker Alert</b>\n\n";
            $msg .= implode("\n\n", $issues);
            $msg .= "\n\n🕐 " . now()->format('d.m.Y H:i:s');
            $telegram->send($msg);
            $this->warn("Alert gesendet: " . count($issues) . " Problem(e)");
        } else {
            $this->info("Tracker healthy: {$online}/{$total} online ({$ratio}%)");
        }
        return 0;
    }
}
