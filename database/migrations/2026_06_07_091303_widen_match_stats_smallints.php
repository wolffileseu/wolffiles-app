<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // smallint unsigned (max 65535) overflowed on parser anomalies
        // (first seen on 'gibs'). Widen to int unsigned (max ~4.29B),
        // matching the damage_* columns.
        DB::statement('ALTER TABLE tracker_player_match_stats
            MODIFY team_kills   INT UNSIGNED NOT NULL DEFAULT 0,
            MODIFY gibs         INT UNSIGNED NOT NULL DEFAULT 0,
            MODIFY kill_assists INT UNSIGNED NOT NULL DEFAULT 0,
            MODIFY self_kills   INT UNSIGNED NOT NULL DEFAULT 0,
            MODIFY team_gibs    INT UNSIGNED NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tracker_player_match_stats
            MODIFY team_kills   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            MODIFY gibs         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            MODIFY kill_assists SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            MODIFY self_kills   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            MODIFY team_gibs    SMALLINT UNSIGNED NOT NULL DEFAULT 0');
    }
};
