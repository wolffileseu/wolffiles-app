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

class ExpireTestSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public int $sessionId,
        public string $mode = 'expired'
    ) {
        $this->onQueue('testserver');
    }

    public function handle(TestserverService $service): void
    {
        $session = TestserverSession::find($this->sessionId);

        if (!$session) {
            Log::warning("ExpireJob: Session #{$this->sessionId} not found");
            return;
        }

        if ($session->isFinished()) {
            Log::info("ExpireJob: Session #{$session->id} already finished ({$session->status}), skipping");
            return;
        }

        if ($this->mode === 'expired'
            && $session->expires_at
            && $session->expires_at->isFuture()) {
            $delay = (int) ceil(now()->diffInSeconds($session->expires_at, false));
            Log::info("ExpireJob: Session #{$session->id} not yet expired, re-delaying {$delay}s");
            self::dispatch($session->id, 'expired')
                ->delay(now()->addSeconds($delay))
                ->onQueue('testserver');
            return;
        }

        Log::info("ExpireJob: Ending session #{$session->id} (mode: {$this->mode})");

        $finalStatus = match ($this->mode) {
            'cancelled' => 'cancelled',
            'forced'    => 'cancelled',
            default     => 'expired',
        };

        $service->endSession($session, $finalStatus);

        Log::info("ExpireJob: Session #{$session->id} closed as '{$finalStatus}'");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ExpireJob: Session #{$this->sessionId} failed permanently: "
                 . $exception->getMessage());

        $session = TestserverSession::find($this->sessionId);
        if ($session && !$session->isFinished()) {
            $session->update([
                'status'        => 'failed',
                'error_message' => 'ExpireJob failed: ' . $exception->getMessage(),
                'ended_at'      => now(),
            ]);
            $session->testserver?->update(['status' => 'idle']);
        }
    }
}
