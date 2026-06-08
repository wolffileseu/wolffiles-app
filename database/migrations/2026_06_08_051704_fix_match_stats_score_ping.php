<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // score was smallint UNSIGNED: negative scores (teamkills/self-kills)
        // wrapped into huge values -> MySQL 1264. Must be SIGNED INT.
        // ping_avg same smallint family, widened pre-emptively to INT UNSIGNED.
        DB::statement('ALTER TABLE tracker_player_match_stats
            MODIFY score    INT NOT NULL DEFAULT 0,
            MODIFY ping_avg INT UNSIGNED NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tracker_player_match_stats
            MODIFY score    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            MODIFY ping_avg SMALLINT UNSIGNED NOT NULL DEFAULT 0');
    }
};
