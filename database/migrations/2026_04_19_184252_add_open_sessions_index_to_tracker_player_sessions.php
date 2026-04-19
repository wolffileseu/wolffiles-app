<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite index to speed up the "who is online on this server" query:
 *   WHERE server_id = X AND ended_at IS NULL ORDER BY started_at
 *
 * Before: MySQL scans all sessions for the server (~35k rows), filters in-memory,
 *         filesorts. ~25-30ms per query.
 * After:  Index does all three steps, returns ~10 rows directly. <1ms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table("tracker_player_sessions", function (Blueprint $table) {
            $table->index(
                ["server_id", "ended_at", "started_at"],
                "tps_open_sessions_idx"
            );
        });
    }

    public function down(): void
    {
        Schema::table("tracker_player_sessions", function (Blueprint $table) {
            $table->dropIndex("tps_open_sessions_idx");
        });
    }
};
