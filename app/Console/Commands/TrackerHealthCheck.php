<?php
namespace App\Console\Commands;
use App\Models\Tracker\TrackerServer;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
class TrackerHealthCheck extends Command
{
    protected $signature = 'tracker:health-check';
    protected $description = 'Check tracker health and notify via Telegram if something is wrong';
    public function handle(): int
    {
        $telegram = new TelegramNotificationService();
        $issues = [];
        $lastPoll = Cache::get('tracker:last_poll_at');
        if (!$lastPoll || now()->diffInMinutes($lastPoll) > 10) {
            $issues[] = '⚠️ <b>Tracker Poll hängt</b> — letzter erfolgreicher Poll: ' . ($lastPoll ? $lastPoll : 'nie');
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
