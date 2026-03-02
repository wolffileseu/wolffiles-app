<?php

namespace App\Console\Commands;

use App\Models\ServerOrder;
use App\Services\ServerProvisioningService;
use Illuminate\Console\Command;

class ServerSuspendExpired extends Command
{
    protected $signature = 'servers:suspend-expired';
    protected $description = 'Suspend servers that have expired (paid_until < now)';

    public function handle(ServerProvisioningService $provisioning): int
    {
        $expired = ServerOrder::where('status', 'active')
            ->where('paid_until', '<', now())
            ->get();

        foreach ($expired as $order) {
            $provisioning->suspend($order);
            $this->warn("Suspended: {$order->server_name} (Order #{$order->id})");

            // Notify user
            if ($order->user) {
                $order->user->notify(new \App\Notifications\ServerSuspended($order));
            }
        }

        $this->info("Suspended {$expired->count()} expired servers.");
        return self::SUCCESS;
    }
}
