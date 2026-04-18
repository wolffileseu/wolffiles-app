<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-match, per-player, per-weapon snapshots.
 *
 * Each row = a single player's weapon stats for a single match.
 * Used for match-history views ("how did wahke play on radar?"),
 * and to compute deltas against tracker_player_weapon_stats
 * (the cumulative totals) when parsing subsequent ws events.
 *
 * Rows are written at match-end time (mapend/mapchange/maprestart)
 * based on the latest ws data we received during that match.
 *
 * Note: the match_id can be null briefly if we receive a ws packet
 * before a map event has opened a match for the server — but under
 * normal flow the match is always open first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_match_player_weapon_stats', function (Blueprint $t) {
            $t->id();

            $t->foreignId('match_id')
                ->constrained('tracker_matches')
                ->cascadeOnDelete();

            $t->foreignId('player_id')
                ->constrained('tracker_players')
                ->cascadeOnDelete();

            // Weapon bit (0..27) — see config/tracker-weapons.php
            $t->unsignedSmallInteger('weapon_bit');

            // Per-match values (NOT cumulative — just this match)
            $t->unsignedInteger('hits')->default(0);
            $t->unsignedInteger('atts')->default(0);
            $t->unsignedInteger('kills')->default(0);
            $t->unsignedInteger('deaths')->default(0);
            $t->unsignedInteger('headshots')->default(0);

            // Per-match cached accuracy (same basis-point format)
            $t->unsignedInteger('accuracy_bp')->default(0);

            $t->timestamp('recorded_at', 3)->nullable();

            $t->timestamps();

            // One row per (match, player, weapon)
            $t->unique(['match_id', 'player_id', 'weapon_bit'], 'mpw_unique');

            // "Which players used the MP40 the most in match X?"
            $t->index(['match_id', 'weapon_bit'], 'mpw_match_weapon_idx');

            // "wahke's weapon history across matches"
            $t->index(['player_id', 'weapon_bit'], 'mpw_player_weapon_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_match_player_weapon_stats');
    }
};
