<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds is_bot flag to tracker_players.
 *
 * Enhanced Tracker receives connect events from bots (e.g. Omni-Bot). Their
 * GUIDs are deterministic per bot config, so they hash consistently. We
 * mark them so the UI can filter or show them separately.
 *
 * Detection heuristic (applied in PlayerPresenceHandler):
 *   - name starts with "^[0-9][BOT]" pattern (e.g. "^o[BOT]^7Halfwit")
 *   - OR name matches /^\[BOT\]/i after color stripping
 *   - OR GUID is all zeros or matches known bot-GUID patterns
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tracker_players', 'is_bot')) {
            Schema::table('tracker_players', function (Blueprint $table) {
                $table->boolean('is_bot')->default(false)->after('is_verified')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tracker_players', 'is_bot')) {
            Schema::table('tracker_players', function (Blueprint $table) {
                $table->dropIndex(['is_bot']);
                $table->dropColumn('is_bot');
            });
        }
    }
};