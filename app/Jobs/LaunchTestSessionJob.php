<?php

namespace App\Jobs;

use App\Models\TestserverSession;
use App\Services\TestserverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LaunchTestSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;
    public int $timeout = 120;

    public function __construct(
        public int $sessionId
    ) {
        $this->onQueue('testserver');
    }

    public function handle(TestserverService $service): void
    {
        $session = TestserverSession::find($this->sessionId);

        if (!$session) {
            Log::warning("LaunchJob: Session #{$this->sessionId} not found");
            return;
        }

        if ($session->status !== 'pending') {
            Log::info("LaunchJob: Session #{$session->id} status is {$session->status}, skipping");
            return;
        }

        $server = $session->testserver;
        $uuid   = $server->pterodactyl_uuid;

        Log::info("LaunchJob: Starting session #{$session->id} on {$server->name}", [
            'map' => $session->map_slug,
            'mod' => $session->mod_name,
        ]);

        $service->lockServer($server);

        // ★ NEU: Map in Container laden BEVOR Restart
        Log::info("LaunchJob: Loading map '{$session->map_slug}' for session #{$session->id}");
        $mapResult = $service->ensureMapLoaded($server, $session->map_slug);

        if (!$mapResult['success']) {
            Log::error("LaunchJob: Map-Load failed", $mapResult);
            $session->update([
                'status'        => 'failed',
                'error_message' => 'Map konnte nicht geladen werden: ' . ($mapResult['error'] ?? 'unknown'),
                'ended_at'      => now(),
            ]);
            $server->update(['status' => 'idle', 'last_error' => $mapResult['error'] ?? 'map load failed']);
            return;
        }

        Log::info("LaunchJob: Map ready", [
            'session' => $session->id,
            'map' => $session->map_slug,
            'source' => $mapResult['source'],
            'cached' => $mapResult['cached'] ?? false,
        ]);

        if (!$service->applySessionAndRestart($session)) {
            Log::error("LaunchJob: applySessionAndRestart failed for session #{$session->id}");
            $server->update(['status' => 'idle', 'last_error' => 'Launch failed: variables/restart']);
            return;
        }

        // Polling: ETLegacy braucht ~21s für Restart
        $maxWaitSeconds = 90;
        $pollInterval   = 3;
        $isReady        = false;

        for ($elapsed = 0; $elapsed < $maxWaitSeconds; $elapsed += $pollInterval) {
            sleep($pollInterval);

            $resources = $service->getResources($uuid);
            if (!$resources) continue;

            $state  = $resources['current_state'] ?? '?';
            $uptime = (int) round(($resources['resources']['uptime'] ?? 0) / 1000);
            $mem    = (int) (($resources['resources']['memory_bytes'] ?? 0) / 1024 / 1024);

            if ($state === 'running' && $uptime > 15 && $mem > 50) {
                $isReady = true;
                Log::info("LaunchJob: Session #{$session->id} ready after ~{$elapsed}s "
                        . "(uptime={$uptime}s, mem={$mem}MB)");
                break;
            }
        }

        if (!$isReady) {
            Log::warning("LaunchJob: Session #{$session->id} did not become ready in {$maxWaitSeconds}s");
            $session->update([
                'status'        => 'failed',
                'error_message' => "Server did not become ready in {$maxWaitSeconds}s",
                'ended_at'      => now(),
            ]);
            $server->update(['status' => 'idle']);
            return;
        }

        $service->activateSession($session);

        ExpireTestSessionJob::dispatch($session->id)
            ->delay(now()->addMinutes($server->max_session_minutes))
            ->onQueue('testserver');

        Log::info("LaunchJob: Session #{$session->id} ACTIVE, "
                . "expires at " . $session->fresh()->expires_at?->format('H:i:s'));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("LaunchJob: Session #{$this->sessionId} permanently failed: "
                 . $exception->getMessage());

        $session = TestserverSession::find($this->sessionId);
        if ($session) {
            $session->update([
                'status'        => 'failed',
                'error_message' => 'Job failed: ' . $exception->getMessage(),
                'ended_at'      => now(),
            ]);
            $session->testserver?->update(['status' => 'idle']);
        }
    }
}
