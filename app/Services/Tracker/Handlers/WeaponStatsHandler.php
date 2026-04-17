<?php

namespace App\Services\Tracker\Handlers;

use App\Models\Tracker\TrackerRawEvent;
use Illuminate\Support\Facades\Log;

/**
 * Handles weapon statistics events:
 *   - wsc <count>                    — header preceding N `ws` packets
 *   - ws <slot> <...stats...>        — per-player weapon stats
 *
 * This handler is a STUB for Phase 3.1.
 * Full parsing logic (3 format variants, 5-values-per-weapon-bit decoding,
 * delta-based growth detection, accuracy trailing-pair parsing) will land
 * in Phase 3.5 as its own dedicated implementation.
 *
 * For now we just mark the event as processed so the queue drains cleanly.
 * The raw payload is preserved in tracker_raw_events forever, so we can
 * re-process historical events once the parser is complete.
 */
class WeaponStatsHandler extends AbstractHandler
{
    public function supports(): array
    {
        return ['wsc', 'ws'];
    }

    public function handle(TrackerRawEvent $event): void
    {
        // Intentionally empty for Phase 3.1.
        // tracker_raw_events already has the payload for later re-processing.

        if (config('tracker.listen.verbose_logging')) {
            Log::debug('WeaponStatsHandler stub: acknowledging event', [
                'cmd' => $event->cmd,
                'size' => $event->size_bytes,
            ]);
        }
    }
}
