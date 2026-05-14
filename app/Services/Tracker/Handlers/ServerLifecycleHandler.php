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

        // Single statement collapses the previous three sequential UPDATEs
        // (last_event_at, event_count increment, first-packet flip) into one.
        // COALESCE preserves the "set first_seen / source_ip only on the first
        // ever event" semantics of the old conditional UPDATE.
        //
        // Why: the old 3-statement pattern held an X-lock on tracker_servers:id
        // for the duration of all three plus the event UPDATE below, and
        // combined with the FK-induced S-lock from tracker_raw_events.server_id
        // produced an S→X-lock-upgrade deadlock under 10 concurrent workers
        // hitting the same hot row. See FINDINGS.md B-1.
        //
        // Order matters: tracker_servers UPDATE BEFORE the event UPDATE.
        // The event UPDATE's FK check on server_id takes an S-lock on the
        // same server row; doing the X-lock-acquiring UPDATE first means we
        // never hold S and try to upgrade to X (the deadlock pattern).
        $receivedAt = $event->received_at?->format('Y-m-d H:i:s.v')
            ?? now()->format('Y-m-d H:i:s.v');

        DB::transaction(function () use ($event, $serverId, $receivedAt) {
            DB::update(
                'UPDATE tracker_servers
                    SET enhanced_last_event_at  = ?,
                        enhanced_event_count    = enhanced_event_count + 1,
                        is_enhanced_tracker     = 1,
                        enhanced_first_seen_at  = COALESCE(enhanced_first_seen_at, ?),
                        enhanced_source_ip      = COALESCE(enhanced_source_ip, ?)
                  WHERE id = ?',
                [$receivedAt, $receivedAt, $event->source_ip, $serverId]
            );

            $event->update(['server_id' => $serverId]);
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

        // Multi-stage matching. Each stage is a stricter match than the next.
        //
        // Stage 1: Match on IP AND source_port — ETDS/ETLegacy sends OOB
        //          packets from sv_port, so source_port equals the
        //          gameserver's public port. This is the MOST specific
        //          match and must win against every other stage,
        //          otherwise multi-server hosts (same IP, different ports)
        //          all collapse onto whichever server was promoted first.
        if ($event->source_port !== null) {
            $server = DB::table('tracker_servers')
                ->where('ip', $event->source_ip)
                ->where('port', $event->source_port)
                ->where('enhanced_disabled', false)
                ->orderByDesc('is_enhanced_tracker') // prefer already-promoted
                ->orderBy('id')
                ->first(['id']);
            if ($server !== null) {
                return (int) $server->id;
            }
        }

        // Stage 2: Admin-pinned enhanced_source_ip (no port). Fallback for
        //          NAT/proxy setups where source_port doesn't equal the
        //          gameserver port. Narrower than plain IP-match because it
        //          only hits servers an admin (or prior auto-discover) has
        //          flagged for this source IP.
        $server = DB::table('tracker_servers')
            ->where('enhanced_source_ip', $event->source_ip)
            ->where('enhanced_disabled', false)
            ->orderBy('id')
            ->first(['id']);
        if ($server !== null) {
            return (int) $server->id;
        }

        // Stage 3: Fall back to IP-only match. Only safe when exactly one
        //          server runs on the IP (common for small hosters). If
        //          multiple servers share the IP, we pick the lowest id
        //          deterministically so the assignment at least stays stable.
        $server = DB::table('tracker_servers')
            ->where('ip', $event->source_ip)
            ->where('enhanced_disabled', false)
            ->orderBy('id')
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
