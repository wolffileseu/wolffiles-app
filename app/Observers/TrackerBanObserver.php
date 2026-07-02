<?php

namespace App\Observers;

use App\Models\Tracker\TrackerBan;
use App\Services\FlagDiscordNotifier;

class TrackerBanObserver
{
    public function updated(TrackerBan $ban): void
    {
        // Fire only when is_public flips to true on an active flag with public evidence.
        if ($ban->wasChanged('is_public') && $ban->is_public && $ban->status === 'active') {
            if ($ban->publicEvidence()->exists()) {
                FlagDiscordNotifier::flagPublished($ban);
            }
        }
    }

    public function created(TrackerBan $ban): void
    {
        // Also handle the case where a flag is created already-public.
        if ($ban->is_public && $ban->status === 'active' && $ban->publicEvidence()->exists()) {
            FlagDiscordNotifier::flagPublished($ban);
        }
    }
}
