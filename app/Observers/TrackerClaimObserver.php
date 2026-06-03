<?php

namespace App\Observers;

use App\Models\Tracker\TrackerClaim;
use App\Services\TelegramNotificationService;
use Illuminate\Support\Facades\Log;

class TrackerClaimObserver
{
    public function created(TrackerClaim $claim): void
    {
        // Notify only for newly-submitted (pending) claims
        if ($claim->status !== 'pending') {
            return;
        }

        try {
            app(TelegramNotificationService::class)->notifyClaimSubmitted($claim);
        } catch (\Throwable $e) {
            // Never let notification failure block the claim creation
            Log::warning('Telegram claim notification failed', [
                'claim_id' => $claim->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
