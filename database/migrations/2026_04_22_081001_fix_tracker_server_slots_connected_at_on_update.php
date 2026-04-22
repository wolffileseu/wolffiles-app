<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * tracker_server_slots.connected_at had ON UPDATE CURRENT_TIMESTAMP(3),
 * which silently reset connected_at every time the row was UPDATEd
 * (e.g. when disconnect_at was set). This caused disconnected_at to
 * appear ~1ms BEFORE connected_at, making whereNull('disconnected_at')
 * queries miss legitimately-open slots — which in turn made
 * WeaponStatsHandler drop every ws-packet silently, leaving
 * tracker_match_player_weapon_stats empty for all non-bot players.
 *
 * Fix: drop the ON UPDATE clause. DEFAULT CURRENT_TIMESTAMP(3) stays
 * so rows without an explicit connected_at still get sensible value,
 * but subsequent updates no longer clobber it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `tracker_server_slots`
            MODIFY COLUMN `connected_at` TIMESTAMP(3) NOT NULL
                DEFAULT CURRENT_TIMESTAMP(3)
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `tracker_server_slots`
            MODIFY COLUMN `connected_at` TIMESTAMP(3) NOT NULL
                DEFAULT CURRENT_TIMESTAMP(3)
                ON UPDATE CURRENT_TIMESTAMP(3)
        ");
    }
};
