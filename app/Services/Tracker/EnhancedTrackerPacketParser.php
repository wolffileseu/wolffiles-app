<?php

namespace App\Services\Tracker;

/**
 * Parses the *envelope* of an ET tracker UDP packet.
 *
 * Does NOT decode per-weapon stats — that happens in ProcessTrackerEventJob
 * so the listener loop stays fast. Here we only extract what we need to:
 *   - validate the packet is actually an ET OOB message
 *   - identify which command it is (start/stop/ws/connect/...)
 *   - store the payload in tracker_raw_events
 *
 * The supported commands come from src/server/sv_tracker.c in ETLegacy.
 */
class EnhancedTrackerPacketParser
{
    /**
     * ET OOB (out-of-band) packets start with four 0xFF bytes.
     * Everything after is ASCII text.
     */
    public const OOB_PREFIX = "\xff\xff\xff\xff";

    /**
     * Commands known from sv_tracker.c in ETLegacy.
     * See https://github.com/etlegacy/etlegacy/pull/2357
     */
    public const KNOWN_COMMANDS = [
        'start',        // Tracker_ServerStart
        'stop',         // Tracker_ServerStop
        'map',          // Tracker_Map
        'maprestart',   // Tracker_MapRestart
        'mapend',       // Tracker_MapEnd
        'p',            // Tracker_Frame keepalive (every 15s)
        'connect',      // Tracker_ClientConnect
        'disconnect',   // Tracker_ClientDisconnect
        'name',         // Tracker_ClientName
        'wsc',          // weapon-stats-count header (precedes N x 'ws')
        'ws',           // individual player weapon stats
        'kill',         // RtCW obituary kill: kill <killer> <victim> <mod>
    ];

    /**
     * Parse a raw UDP datagram into a structured envelope.
     *
     * Returns an array with at minimum:
     *   - valid:   bool      was this a well-formed ET OOB packet
     *   - cmd:     string    command word (may be 'unknown' if not recognized)
     *   - payload: string    text after the OOB prefix, newlines stripped
     *   - size:    int       original packet size in bytes
     *
     * Never throws — malformed packets just come back with valid=false.
     */
    public function parseEnvelope(string $rawData): array
    {
        $size = strlen($rawData);

        // Too small to be anything meaningful (4 bytes OOB + at least 1 char)
        if ($size < 5) {
            return [
                'valid' => false,
                'cmd' => 'invalid',
                'payload' => '',
                'size' => $size,
                'reason' => 'packet too short',
            ];
        }

        // Must start with the OOB marker
        if (!str_starts_with($rawData, self::OOB_PREFIX)) {
            return [
                'valid' => false,
                'cmd' => 'invalid',
                'payload' => '',
                'size' => $size,
                'reason' => 'missing OOB prefix',
            ];
        }

        // Strip the prefix and any trailing newline
        $payload = substr($rawData, 4);
        $payload = rtrim($payload, "\n\r\0");

        if ($payload === '') {
            return [
                'valid' => false,
                'cmd' => 'invalid',
                'payload' => '',
                'size' => $size,
                'reason' => 'empty payload',
            ];
        }

        // Extract first whitespace-delimited token as command
        $firstSpace = strpos($payload, ' ');
        $cmd = $firstSpace === false ? $payload : substr($payload, 0, $firstSpace);

        // Sanity: commands should be short ASCII tokens. If we see something weird,
        // it's probably garbage from a non-ET sender.
        if (strlen($cmd) > 16 || !preg_match('/^[a-z_]+$/', $cmd)) {
            return [
                'valid' => false,
                'cmd' => 'invalid',
                'payload' => $payload,
                'size' => $size,
                'reason' => 'malformed command token',
            ];
        }

        // Unknown command is still valid — we accept it, log it, and may learn
        // about new commands added to the tracker over time.
        $isKnown = in_array($cmd, self::KNOWN_COMMANDS, true);

        return [
            'valid' => true,
            'cmd' => $isKnown ? $cmd : 'unknown',
            'cmd_raw' => $cmd,
            'payload' => $payload,
            'size' => $size,
            'known' => $isKnown,
        ];
    }
}
