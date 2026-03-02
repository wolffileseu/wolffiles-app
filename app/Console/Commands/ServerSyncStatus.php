<?php

namespace App\Console\Commands;

use App\Models\ServerOrder;
use App\Services\PterodactylService;
use Illuminate\Console\Command;

class ServerSyncStatus extends Command
{
    protected $signature = 'servers:sync-status';
    protected $description = 'Sync server status with Pterodactyl panel';

    public function handle(PterodactylService $pterodactyl): int
    {
        if (!$pterodactyl->isConfigured()) {
            $this->warn('Pterodactyl not configured. Skipping sync.');
            return self::SUCCESS;
        }

        $orders = ServerOrder::whereIn('status', ['active', 'suspended'])
            ->whereNotNull('pterodactyl_server_id')
            ->get();

        foreach ($orders as $order) {
            try {
                $resources = $pterodactyl->getServerResources($order->pterodactyl_server_id);
                if ($resources) {
                    $order->update([
                        'last_status_check' => now(),
                        'config' => array_merge($order->config ?? [], [
                            'current_state' => $resources['current_state'] ?? 'unknown',
                            'memory_bytes' => $resources['resources']['memory_bytes'] ?? 0,
                            'cpu_absolute' => $resources['resources']['cpu_absolute'] ?? 0,
                            'disk_bytes' => $resources['resources']['disk_bytes'] ?? 0,
                        ]),
                    ]);
                }
            } catch (\Exception $e) {
                $this->warn("Failed to sync: {$order->server_name} - {$e->getMessage()}");
            }
        }

        $this->info("Synced {$orders->count()} servers.");
        return self::SUCCESS;
    }
}
