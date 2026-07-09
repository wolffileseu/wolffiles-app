<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;
use Throwable;
use App\Models\Tracker\TrackerRawEvent;
use App\Services\Tracker\Handlers\AbstractHandler;
use App\Services\Tracker\Handlers\MatchLifecycleHandler;
use App\Services\Tracker\Handlers\PlayerAliasHandler;
use App\Services\Tracker\Handlers\PlayerPresenceHandler;
use App\Services\Tracker\Handlers\ServerLifecycleHandler;
use App\Services\Tracker\Handlers\WeaponStatsHandler;
use App\Services\Tracker\Handlers\KillHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Processes a single tracker_raw_events row.
 *
 * Looks up the appropriate handler for the event's command, runs it,
 * and marks the event as processed.
 *
 * Pipeline per event:
 *   1. ServerLifecycleHandler runs unconditionally — resolves server_id,
 *      flips is_enhanced_tracker on first packet, bumps event counter.
 *   2. A command-specific handler runs if one exists (connect → presence,
 *      name → alias, map/mapend → match lifecycle, ws → weapon stats).
 *   3. Event gets marked as processed=true.
 *
 * Failures: the job retries 3 times with exponential backoff. After final
 * retry the error is logged and processing_error is filled on the event.
 */
class ProcessTrackerEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function __construct(
        public readonly int $rawEventId,
    ) {
        $this->onConnection(config('tracker.processing.queue_connection', 'database'));
        $this->onQueue(config('tracker.processing.queue_name', 'tracker'));
    }

    public function handle(): void
    {
        $event = TrackerRawEvent::find($this->rawEventId);
        if (!$event) {
            Log::warning('ProcessTrackerEventJob: raw event not found', [
                'raw_event_id' => $this->rawEventId,
            ]);
            return;
        }

        if ($event->processed) {
            return; // idempotent
        }

        try {
            // Step 1 — unconditional server-level handling.
            // Resolves/creates the tracker_servers row, sets server_id on the
            // event, flips is_enhanced_tracker, bumps counters.
            (new ServerLifecycleHandler())->handle($event);

            // Refresh to see server_id set by the handler above
            $event->refresh();

            // Skip command-specific handling for banned servers. The
            // ServerLifecycleHandler already linked server_id but did not
            // promote the server; running presence/kill/weapon handlers
            // would re-populate players and stats for a banned server.
            $serverBanned = $event->server_id !== null
                && DB::table('tracker_servers')
                    ->where('id', $event->server_id)
                    ->value('status') === 'banned';

            // Step 2 — command-specific handling
            if (!$serverBanned) {
                $handler = $this->resolveHandler($event->cmd);
                if ($handler !== null) {
                    $handler->handle($event);
                }
            }

            // Step 3 — done
            $event->update([
                'processed' => true,
                'processed_at' => now(),
                'processing_error' => null,
            ]);
        } catch (Throwable $e) {
            Log::error('ProcessTrackerEventJob failed', [
                'raw_event_id' => $this->rawEventId,
                'cmd' => $event->cmd,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 1500),
            ]);

            $event->update([
                'processing_error' => substr($e->getMessage(), 0, 255),
            ]);

            throw $e; // let Laravel handle retry
        }
    }

    /**
     * Find a handler that supports this command.
     * ServerLifecycleHandler runs unconditionally and is NOT in this map.
     */
    private function resolveHandler(string $cmd): ?AbstractHandler
    {
        $handlers = [
            new PlayerPresenceHandler(),
            new PlayerAliasHandler(),
            new MatchLifecycleHandler(),
            new WeaponStatsHandler(),
            new KillHandler(),
        ];

        foreach ($handlers as $handler) {
            if (in_array($cmd, $handler->supports(), true)) {
                return $handler;
            }
        }

        return null;
    }
}
