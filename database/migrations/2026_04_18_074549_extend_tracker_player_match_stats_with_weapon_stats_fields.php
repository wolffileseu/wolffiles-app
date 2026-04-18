<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends tracker_player_match_stats with fields from the ws-packet
 * damage section and skill/rating tail that weren't previously captured.
 *
 * Source: ETLegacy G_createStats() in src/game/g_match.c:543
 *
 * New fields:
 *   - team_damage_given / team_damage_received  (friendly-fire tracking)
 *   - gibs, kill_assists, self_kills, team_gibs (kill-variant counts)
 *   - time_played_pct      (% of match this player was active, NOT accuracy)
 *   - skill_rating + delta (FEATURE_RATING compiled into mod, mu-3*sigma)
 *   - prestige             (FEATURE_PRESTIGE)
 *   - raw_skills           (skill_mask + per-skill XP as JSON, for later parsing)
 *
 * IMPORTANT: self_kills is distinct from the existing 'suicides' column.
 *   - suicides = Poller-side, counts /kill console commands
 *   - self_kills = ws-packet, counts deaths by own-dynamite/grenade/rifle-nade
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_player_match_stats', function (Blueprint $t) {
            // Friendly-fire damage
            $t->unsignedInteger('team_damage_given')->default(0)->after('damage_received');
            $t->unsignedInteger('team_damage_received')->default(0)->after('team_damage_given');

            // Kill variants from ws damage section
            $t->unsignedSmallInteger('gibs')->default(0)->after('team_kills');
            $t->unsignedSmallInteger('kill_assists')->default(0)->after('gibs');
            $t->unsignedSmallInteger('self_kills')->default(0)->after('kill_assists');
            $t->unsignedSmallInteger('team_gibs')->default(0)->after('self_kills');

            // Participation percentage (different semantic from playtime_seconds!)
            // ws formula: 100 * time_played / (time_axis + time_allies)
            $t->decimal('time_played_pct', 5, 2)->nullable()->after('playtime_seconds');

            // FEATURE_RATING tail (nullable — not all mods compile it in)
            $t->float('skill_rating')->nullable()->after('time_played_pct');
            $t->float('skill_rating_delta')->nullable()->after('skill_rating');

            // FEATURE_PRESTIGE tail
            $t->unsignedSmallInteger('prestige')->nullable()->after('skill_rating_delta');

            // Raw skill section preserved as JSON for forward-compatibility.
            // Structure: {"mask": 19, "mode": "pair"|"single",
            //             "skills": {"0": {"current": 13, "delta": 13}, ...}}
            $t->json('raw_skills')->nullable()->after('prestige');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_player_match_stats', function (Blueprint $t) {
            $t->dropColumn([
                'team_damage_given',
                'team_damage_received',
                'gibs',
                'kill_assists',
                'self_kills',
                'team_gibs',
                'time_played_pct',
                'skill_rating',
                'skill_rating_delta',
                'prestige',
                'raw_skills',
            ]);
        });
    }
};
