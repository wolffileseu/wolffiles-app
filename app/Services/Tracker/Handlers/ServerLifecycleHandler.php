<?php

namespace App\Services\Tracker\Handlers;

use App\Models\Tracker\TrackerRawEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles server-lifecycle events:
 *   - start:       ET server started tracker (initial handshake)
 *   - stop:        ET server shutting down cleanly
 *   - p:           keepalive, emitted every ~15 seconds
 *
 * Responsibilities:
 *   - Find the tracker_servers row by source_ip (or create one for auto-discovery)
 *   - Mark server as is_enhanced_tracker=true on first packet
 *   - Bump enhanced_last_event_at and enhanced_event_count
 *   - Link the raw event back to its server_id
 */
class ServerLifecycleHandler extends AbstractHandler
{
    public function supports(): array
    {
        return ['start', 'stop', 'p'];
    }

    public function handle(TrackerRawEvent $event): void
    {
        $serverId = $this->resolveServerId($event);
        if ($serverId === null) {
            // Auto-discover disabled and server unknown — drop silently.
            return;
        }

        DB::transaction(function () use ($event, $serverId) {
            // Bump the event onto its server
            $event->update(['server_id' => $serverId]);

            // Touch server-level state
            $updates = [
                'enhanced_last_event_at' => $event->received_at,
            ];

            // Event counter is updated via raw SQL to avoid read-modify-write races.
            DB::table('tracker_servers')
                ->where('id', $serverId)
                ->update($updates);

            DB::table('tracker_servers')
                ->where('id', $serverId)
                ->increment('enhanced_event_count');

            // First-packet-ever: flip the flag and record the milestone
            DB::table('tracker_servers')
                ->where('id', $serverId)
                ->where('is_enhanced_tracker', false)
                ->update([
                    'is_enhanced_tracker' => true,
                    'enhanced_first_seen_at' => $event->received_at,
                    'enhanced_source_ip' => $event->source_ip,
                ]);
        });

        // Log lifecycle events (but not keepalives — too noisy)
        if (in_array($event->cmd, ['start', 'stop'], true)) {
            Log::info('Tracker server lifecycle', [
                'cmd' => $event->cmd,
                'server_id' => $serverId,
                'source_ip' => $event->source_ip,
            ]);
        }
    }

    /**
     * Find the server_id for this event by source_ip.
     * Returns existing id, creates a new row if auto-discover is enabled, or null.
     */
    private function resolveServerId(TrackerRawEvent $event): ?int
    {
        // Fast path: server_id already set by a previous handler
        if ($event->server_id !== null) {
            return $event->server_id;
        }

        // Prefer the enhanced_source_ip (exact sender match) if any server claims it,
        // then fall back to the regular ip column from the Server Poller.
        $server = DB::table('tracker_servers')
            ->where(function ($query) use ($event) {
                $query->where('enhanced_source_ip', $event->source_ip)
                    ->orWhere('ip', $event->source_ip);
            })
            ->where('enhanced_disabled', false)
            ->orderByDesc('enhanced_source_ip')   // prefer exact-IP match if multiple
            ->first(['id']);

        if ($server !== null) {
            return (int) $server->id;
        }

        if (!config('tracker.auto_discover.enabled', true)) {
            Log::debug('Tracker: unknown server IP, auto-discover disabled', [
                'ip' => $event->source_ip,
            ]);
            return null;
        }

        // Auto-create a placeholder tracker_servers row.
        // Most fields will be filled in by the Server Poller on next poll cycle.
        $now = Carbon::now();
        $id = DB::table('tracker_servers')->insertGetId([
            'game_id' => $this->guessGameIdForIp($event->source_ip),
            'ip' => $event->source_ip,
            'port' => 27960,                     // best-guess default; Poller will correct
            'hostname' => 'Unknown ('.$event->source_ip.')',
            'hostname_clean' => 'Unknown ('.$event->source_ip.')',
            'hostname_html' => 'Unknown ('.$event->source_ip.')',
            'is_manually_added' => false,
            'status' => 'pending',
            'is_enhanced_tracker' => true,
            'enhanced_first_seen_at' => $event->received_at,
            'enhanced_source_ip' => $event->source_ip,
            'enhanced_event_count' => 0,
            'enhanced_disabled' => false,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Log::info('Tracker: auto-discovered new server', [
            'server_id' => $id,
            'ip' => $event->source_ip,
        ]);

        return $id;
    }

    /**
     * Best-effort game_id guess when auto-creating a server row.
     * Defaults to 1 (ET) since sv_tracker2 is ET-specific.
     */
    private function guessGameIdForIp(string $ip): int
    {
        // Could later query Server Poller if it knows a different game for this IP,
        // but for the MVP Enhanced Tracker is ET-only.
        return 1;
    }
}
