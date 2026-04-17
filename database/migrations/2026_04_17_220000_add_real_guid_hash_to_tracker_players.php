<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds real_guid_hash to tracker_players.
 *
 * Context: the existing `guid_hash` column is NOT an ET-GUID hash — it's
 * sha256(strtolower(name_clean)) as computed by PlayerTrackingService::254.
 * That's fine for the Server Poller (which only sees names via network
 * queries), but the Enhanced Tracker now gets REAL ET GUIDs from sv_tracker2
 * packets, so we need a second column for GUID-based identity.
 *
 * Matching strategy in PlayerPresenceHandler::handleConnect():
 *   1. SELECT WHERE real_guid_hash = sha256(lower(real_guid))     → already linked
 *   2. SELECT WHERE guid_hash = sha256(lower(name_clean))          → poller match
 *   3. INSERT new row                                              → new / bot
 *
 * Nullable because Poller-only players never see a real GUID.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tracker_players', 'real_guid_hash')) {
            Schema::table('tracker_players', function (Blueprint $table) {
                $table->string('real_guid_hash', 64)->nullable()->after('guid_hash')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tracker_players', 'real_guid_hash')) {
            Schema::table('tracker_players', function (Blueprint $table) {
                $table->dropIndex(['real_guid_hash']);
                $table->dropColumn('real_guid_hash');
            });
        }
    }
};
