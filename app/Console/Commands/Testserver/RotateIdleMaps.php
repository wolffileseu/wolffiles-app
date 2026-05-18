<?php

namespace App\Console\Commands\Testserver;

use App\Models\Testserver;
use App\Services\TestserverService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RotateIdleMaps extends Command
{
    protected $signature = 'testserver:rotate-idle {--force : Force rotation regardless of last rotation time}';
    protected $description = 'Rotate map + mod on idle testservers every ~2h for variety';

    public function handle(TestserverService $service): int
    {
        $servers = Testserver::where('enabled', true)
            ->where('public_visible', true)
            ->where('status', 'idle')
            ->orderBy('slot_number')
            ->get();

        if ($servers->isEmpty()) {
            $this->info('No idle servers to rotate.');
            return self::SUCCESS;
        }

        $force = $this->option('force');
        $rotated = 0;
        $skipped = 0;

        foreach ($servers as $server) {
            // Skip wenn vor weniger als 110min rotiert (gibt Buffer für 2h-Cron)
            if (!$force && $server->last_rotation_at && $server->last_rotation_at->diffInMinutes(now()) < 110) {
                $mins = (int) $server->last_rotation_at->diffInMinutes(now());
                $this->line("  Skip Server {$server->slot_number} (last rotation {$mins}min ago)");
                $skipped++;
                continue;
            }

            $this->info("Rotating Server {$server->slot_number}...");

            try {
                $service->enterIdleMode($server);
                $server->last_rotation_at = now();
                $server->save();
                $rotated++;
                $this->line("  ✅ Server {$server->slot_number} rotated");
                Log::info('Testserver: idle rotation', [
                    'slot' => $server->slot_number,
                    'mod'  => $server->current_idle_mod,
                ]);
                sleep(2);
            } catch (\Throwable $e) {
                Log::error("Testserver: rotation failed for slot {$server->slot_number}: " . $e->getMessage());
                $this->error("  ❌ Server {$server->slot_number} failed: " . $e->getMessage());
            }
        }

        $this->info("Done: {$rotated} rotated, {$skipped} skipped");
        return self::SUCCESS;
    }
}
