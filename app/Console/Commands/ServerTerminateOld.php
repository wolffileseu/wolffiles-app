<?php

namespace App\Console\Commands;

use App\Models\ServerOrder;
use App\Services\ServerProvisioningService;
use Illuminate\Console\Command;

class ServerTerminateOld extends Command
{
    protected $signature = 'servers:terminate-old {--days=30 : Days after suspension to terminate}';
    protected $description = 'Terminate servers suspended for more than 30 days';

    public function handle(ServerProvisioningService $provisioning): int
    {
        $days = (int) $this->option('days');

        $old = ServerOrder::where('status', 'suspended')
            ->where('suspended_at', '<', now()->subDays($days))
            ->get();

        foreach ($old as $order) {
            // Send final warning 7 days before
            $daysUntilTermination = now()->diffInDays($order->suspended_at->addDays($days), false);
            if ($daysUntilTermination <= 7 && $daysUntilTermination > 0) {
                $cacheKey = "server_termwarn_{$order->id}";
                if (!cache()->has($cacheKey) && $order->user) {
                    $order->user->notify(new \App\Notifications\ServerTerminationWarning($order, ( int) $daysUntilTermination));
                    cache()->put($cacheKey, true, now()->addHours(23));
                    $this->warn("Termination warning sent: {$order->server_name}");
                }
                continue;
            }

            if ($daysUntilTermination <= 0) {
                $provisioning->terminate($order);
                $this->error("Terminated: {$order->server_name} (Order #{$order->id})");

                if ($order->user) {
                    $order->user->notify(new \App\Notifications\ServerTerminated($order));
                }
            }
        }

        $this->info("Processed {$old->count()} suspended servers.");
        return self::SUCCESS;
    }
}
