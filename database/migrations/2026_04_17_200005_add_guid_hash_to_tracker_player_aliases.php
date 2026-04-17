<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The tracker_player_aliases table was created by an earlier system and has 27k+ rows.
 * We preserve it as-is and only add an optional guid_hash column for fast joins with
 * Enhanced Tracker events (which identify players primarily by guid_hash).
 *
 * guid_hash is nullable because existing rows don't have it — they only link via player_id.
 * The Enhanced Tracker will populate it for new alias entries going forward.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tracker_player_aliases', 'guid_hash')) {
            Schema::table('tracker_player_aliases', function (Blueprint $table) {
                $table->string('guid_hash', 64)->nullable()->after('player_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tracker_player_aliases', 'guid_hash')) {
            Schema::table('tracker_player_aliases', function (Blueprint $table) {
                $table->dropIndex(['guid_hash']);
                $table->dropColumn('guid_hash');
            });
        }
    }
};
