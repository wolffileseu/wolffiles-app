<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slot-to-player mapping for Enhanced Tracker events.
 *
 * When ETLegacy sends "ws <slot> 0 <mask> ..." we need to know which
 * player currently occupies that slot on that server. The connect/disconnect
 * packets give us the mapping, but are processed by PlayerPresenceHandler
 * which so far only upserted tracker_players rows.
 *
 * This table persists the mapping so that WeaponStatsHandler can do a
 * simple O(1) lookup:
 *   SELECT player_id FROM tracker_server_slots
 *    WHERE server_id = ? AND slot = ? AND disconnected_at IS NULL
 *    ORDER BY connected_at DESC LIMIT 1
 *
 * No UNIQUE on (server, slot) because over time the same slot will be
 * occupied by many different players (connect -> disconnect -> new connect).
 * The index with disconnected_at makes the current-occupant query fast.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_server_slots', function (Blueprint $t) {
            $t->id();

            $t->foreignId('server_id')
                ->constrained('tracker_servers')
                ->cascadeOnDelete();

            // ET MAX_CLIENTS is 64, so unsigned tinyint (0..255) is plenty.
            $t->unsignedTinyInteger('slot');

            $t->foreignId('player_id')
                ->constrained('tracker_players')
                ->cascadeOnDelete();

            $t->timestamp('connected_at', 3);
            $t->timestamp('disconnected_at', 3)->nullable();

            $t->timestamps();

            // Primary lookup: "who is currently on slot X of server Y?"
            $t->index(['server_id', 'slot', 'disconnected_at'], 'tss_lookup_idx');

            // Player history: "what slots did wahke occupy, when?"
            $t->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_server_slots');
    }
};
