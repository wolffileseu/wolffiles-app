<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cumulative per-player, per-weapon stats from ETLegacy 'ws' packets.
 *
 * Each row = one player's lifetime totals for one weapon (MP40, Thompson,
 * Garand, ...). Updated on every parsed 'ws' event: we take the delta
 * between the packet's values and what we already have, and add it.
 *
 * Weapon bits follow extWeaponStats_t in bg_public.h (WS_KNIFE=0..WS_SYRINGE=27,
 * WS_MAX=28). See config/tracker-weapons.php for the bit -> weapon-name mapping.
 *
 * accuracy_bp is stored as integer basis points (hits*10000/atts) so that
 * "Top MP40 accuracy with at least 500 shots" queries stay indexable without
 * floats. 42.50% -> accuracy_bp = 4250. The UI divides by 100 for display.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_player_weapon_stats', function (Blueprint $t) {
            $t->id();

            $t->foreignId('player_id')
                ->constrained('tracker_players')
                ->cascadeOnDelete();

            // Weapon bit (0..27). Widened to smallint in case future mods extend WS_*.
            $t->unsignedSmallInteger('weapon_bit');

            // Cumulative lifetime values — all unsigned (stats only grow).
            $t->unsignedInteger('total_hits')->default(0);
            $t->unsignedInteger('total_atts')->default(0);
            $t->unsignedInteger('total_kills')->default(0);
            $t->unsignedInteger('total_deaths')->default(0);
            $t->unsignedInteger('total_headshots')->default(0);

            // Cached accuracy in basis points: floor(total_hits * 10000 / total_atts).
            // 4250 = 42.50%. Avoids floats in indexes for ranking queries.
            $t->unsignedInteger('accuracy_bp')->default(0);

            // First time we saw this weapon used, latest update, for trending.
            $t->timestamp('first_seen_at', 3)->nullable();
            $t->timestamp('last_updated_at', 3)->nullable();

            $t->timestamps();

            // One row per (player, weapon).
            $t->unique(['player_id', 'weapon_bit']);

            // Rankings: "Top 10 MP40 accuracy with enough shots"
            $t->index(['weapon_bit', 'accuracy_bp']);
            // Rankings: "Most kills with the MP40"
            $t->index(['weapon_bit', 'total_kills']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_player_weapon_stats');
    }
};
