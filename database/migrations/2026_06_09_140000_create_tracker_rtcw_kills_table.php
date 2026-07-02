<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RtCW (iortcw) obituary-based kill events.
 *
 * RtCW + Omnibot does not expose ET-style weapon stats (no statsall/ws path),
 * so for RtCW we capture individual kills parsed from the game's "Kill:" log
 * lines, forwarded by the engine as:  kill <killer> <victim> <mod>
 *
 * This is intentionally separate from the ET weapon-stats tables
 * (tracker_player_match_stats / tracker_player_weapon_stats), which model a
 * different, richer dataset (accuracy, hits, damage). Mixing RtCW kills into
 * those would leave most columns null and confuse ET aggregation.
 *
 * Team is NOT stored: it lives in the mod's sess.sessionTeam and is not
 * available to the engine, so RtCW kills cannot reliably distinguish
 * team-kills. Column kept nullable for a possible future mod-side patch.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tracker_rtcw_kills', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('server_id');
            $table->unsignedBigInteger('match_id')->nullable();

            // Raw slots as sent by the engine (0..63, or a world/sentinel value)
            $table->smallInteger('killer_slot');
            $table->smallInteger('victim_slot');

            // Resolved players (nullable: bots, or slot not resolvable)
            $table->unsignedBigInteger('killer_player_id')->nullable();
            $table->unsignedBigInteger('victim_player_id')->nullable();

            // means_of_death_t index + decoded weapon/category
            $table->unsignedSmallInteger('mod_index');
            $table->string('weapon_key', 32);              // e.g. 'thompson'
            $table->string('category', 16);                // weapon|environment|suicide|action|unknown

            // Convenience flags derived from category
            $table->boolean('is_frag')->default(false);    // real player frag credited to killer
            $table->boolean('is_world')->default(false);   // environment/suicide death
            $table->boolean('killer_is_bot')->default(false);
            $table->boolean('victim_is_bot')->default(false);

            $table->timestamp('killed_at');
            $table->timestamps();

            $table->index(['server_id', 'killed_at']);
            $table->index(['match_id']);
            $table->index(['killer_player_id', 'killed_at']);
            $table->index(['victim_player_id', 'killed_at']);
            $table->index(['weapon_key']);

            $table->foreign('server_id')
                ->references('id')->on('tracker_servers')
                ->cascadeOnDelete();
            $table->foreign('match_id')
                ->references('id')->on('tracker_matches')
                ->nullOnDelete();
            $table->foreign('killer_player_id')
                ->references('id')->on('tracker_players')
                ->nullOnDelete();
            $table->foreign('victim_player_id')
                ->references('id')->on('tracker_players')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_rtcw_kills');
    }
};
