<?php

namespace App\Services\Tracker;

/**
 * Wraps the Server Poller's identity-hash algorithm so Enhanced Tracker can
 * match against existing tracker_players rows.
 *
 * The Poller uses sha256(strtolower(name_clean)) — see
 * PlayerTrackingService::254. We replicate that exact pipeline here to ensure
 * Enhanced's name → hash produces the same value the Poller stored.
 *
 * Keep this class in lockstep with PlayerTrackingService. If that service
 * changes its hashing, update this one too.
 */
class PollerHashService
{
    /**
     * Compute the Poller's guid_hash for a given name (possibly with color codes).
     *
     * Pipeline:
     *   "^1wahke"                → strip colors → "wahke"
     *                             → strtolower  → "wahke"
     *                             → sha256      → b526...
     */
    public function hashFromName(string $name): string
    {
        return hash('sha256', \App\Services\Tracker\ColorCodeService::normalizeKey($name));
    }

    /**
     * Compute the Enhanced Tracker's real_guid_hash from an ET GUID.
     * GDPR-safe — the raw GUID is never stored anywhere.
     */
    public function hashFromRealGuid(string $rawGuid): string
    {
        return hash('sha256', strtolower(trim($rawGuid)));
    }

    /**
     * Strip ET color codes (^0..^9, ^a..^z) from a name.
     * Matches PlayerTrackingService's cleaning behavior.
     */
    public function stripColorCodes(string $name): string
    {
        return preg_replace('/\^./', '', $name) ?? $name;
    }
}
