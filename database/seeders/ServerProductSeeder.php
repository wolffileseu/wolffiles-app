<?php

namespace Database\Seeders;

use App\Models\ServerProduct;
use Illuminate\Database\Seeder;

class ServerProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'ET: Legacy Server',
                'slug' => 'etl-server',
                'game' => 'etl',
                'slots' => 24,         // Default Slots
                'min_slots' => 2,
                'max_slots' => 64,
                // Slot-basierte Preise
                'price_per_slot_daily' => 0.05,
                'price_per_slot_weekly' => 0.25,
                'price_per_slot_monthly' => 0.50,
                'price_per_slot_quarterly' => 1.20,
                // Feste Paketpreise (für Anzeige als Beispiel)
                'price_daily' => 1.20,
                'price_weekly' => 6.00,
                'price_monthly' => 12.00,
                'price_quarterly' => 28.80,
                // Resources pro Slot
                'memory_mb' => 1024,
                'base_memory_mb' => 256,
                'memory_per_slot_mb' => 32,
                'cpu_percent' => 100,
                'cpu_per_slot_percent' => 5,
                'disk_mb' => 4096,
                'base_disk_mb' => 1024,
                'disk_per_slot_mb' => 128,
                'description' => 'ET: Legacy Gameserver mit voller Mod-Unterstützung. Preis pro Slot, ab 2 Slots buchbar.',
                'features' => ['FastDL', 'DDoS Protection', 'Daily Backups', 'Web Panel', 'Mod Support', 'Wolffiles Integration'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Enemy Territory 2.60b Server',
                'slug' => 'et-server',
                'game' => 'et',
                'slots' => 24,
                'min_slots' => 2,
                'max_slots' => 64,
                'price_per_slot_daily' => 0.05,
                'price_per_slot_weekly' => 0.25,
                'price_per_slot_monthly' => 0.50,
                'price_per_slot_quarterly' => 1.20,
                'price_daily' => 1.20,
                'price_weekly' => 6.00,
                'price_monthly' => 12.00,
                'price_quarterly' => 28.80,
                'memory_mb' => 1024,
                'base_memory_mb' => 256,
                'memory_per_slot_mb' => 32,
                'cpu_percent' => 100,
                'cpu_per_slot_percent' => 5,
                'disk_mb' => 4096,
                'base_disk_mb' => 1024,
                'disk_per_slot_mb' => 128,
                'description' => 'Klassischer ET 2.60b Server. Kompatibel mit allen Mods.',
                'features' => ['FastDL', 'DDoS Protection', 'Daily Backups', 'Web Panel', 'Mod Support'],
                'sort_order' => 2,
            ],
            [
                'name' => 'RtCW Server',
                'slug' => 'rtcw-server',
                'game' => 'rtcw',
                'slots' => 16,
                'min_slots' => 2,
                'max_slots' => 32,
                'price_per_slot_daily' => 0.05,
                'price_per_slot_weekly' => 0.25,
                'price_per_slot_monthly' => 0.50,
                'price_per_slot_quarterly' => 1.20,
                'price_daily' => 0.80,
                'price_weekly' => 4.00,
                'price_monthly' => 8.00,
                'price_quarterly' => 19.20,
                'memory_mb' => 512,
                'base_memory_mb' => 192,
                'memory_per_slot_mb' => 20,
                'cpu_percent' => 75,
                'cpu_per_slot_percent' => 4,
                'disk_mb' => 2048,
                'base_disk_mb' => 512,
                'disk_per_slot_mb' => 64,
                'description' => 'Return to Castle Wolfenstein Multiplayer Server.',
                'features' => ['DDoS Protection', 'Daily Backups', 'Web Panel', 'OSP Support'],
                'sort_order' => 3,
            ],
        ];

        foreach ($products as $product) {
            ServerProduct::updateOrCreate(
                ['slug' => $product['slug']],
                $product
            );
        }
    }
}
