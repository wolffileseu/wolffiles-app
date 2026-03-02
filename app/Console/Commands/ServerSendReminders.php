<?php

namespace App\Console\Commands;

use App\Models\ServerOrder;
use App\Models\User;
use App\Notifications\ServerExpiryReminder;
use Illuminate\Console\Command;

class ServerSendReminders extends Command
{
    protected $signature = 'servers:send-reminders';
    protected $description = 'Send expiry reminders for servers (7d, 3d, 1d before)';

    public function handle(): int
    {
        $reminders = [7, 3, 1];

        foreach ($reminders as $days) {
            $orders = ServerOrder::where('status', 'active')
                ->whereDate('paid_until', now()->addDays($days)->toDateString())
                ->with('user')
                ->get();

            foreach ($orders as $order) {
                // Prevent duplicate reminders via cache
                $cacheKey = "server_reminder_{$order->id}_{$days}d";
                if (cache()->has($cacheKey)) continue;

                $order->user->notify(new ServerExpiryReminder($order, $days));
                cache()->put($cacheKey, true, now()->addHours(23));

                $this->info("Reminder sent: {$order->server_name} ({$days}d) to {$order->user->email}");
            }
        }

        return self::SUCCESS;
    }
}
