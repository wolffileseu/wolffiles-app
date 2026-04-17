<?php

namespace App\Services\Tracker\Handlers;

use App\Models\Tracker\TrackerRawEvent;

/**
 * Base class for Enhanced Tracker event handlers.
 *
 * Each concrete handler deals with one category of commands:
 *   - ServerLifecycleHandler: start, stop, p
 *   - MatchLifecycleHandler:  map, mapend, maprestart
 *   - PlayerPresenceHandler:  connect, disconnect
 *   - PlayerAliasHandler:     name
 *   - WeaponStatsHandler:     wsc, ws
 *
 * Contract for all handlers:
 *   - idempotent (may be retried by the queue)
 *   - never throw on malformed input (log & return)
 *   - wrap multi-table updates in DB::transaction()
 */
abstract class AbstractHandler
{
    /**
     * Commands this handler is responsible for.
     * Used by ProcessTrackerEventJob to route events.
     *
     * @return array<string>
     */
    abstract public function supports(): array;

    /**
     * Process a single raw event.
     * The event is already persisted in tracker_raw_events;
     * the handler's job is to interpret it and update derived tables.
     */
    abstract public function handle(TrackerRawEvent $event): void;

    /**
     * Strip ET color codes (^0..^9, ^a..^z) from a name.
     *   "^1wahke"             → "wahke"
     *   "^o[BOT]^7Halfwit"    → "[BOT]Halfwit"
     */
    protected function stripColorCodes(string $name): string
    {
        return preg_replace('/\^./', '', $name) ?? $name;
    }

    /**
     * Detect whether a player looks like an Omni-Bot.
     * ET Legacy bots use the pattern "^o[BOT]^7<name>".
     */
    protected function nameLooksLikeBot(string $name): bool
    {
        $clean = $this->stripColorCodes($name);
        return (bool) preg_match('/^\s*\[BOT\]/i', $clean);
    }

    /**
     * Hash a raw ET GUID using the project convention.
     * We never store raw GUIDs — SHA-256 hashes only (GDPR).
     */
    protected function hashGuid(string $rawGuid): string
    {
        return hash('sha256', strtolower(trim($rawGuid)));
    }
}
