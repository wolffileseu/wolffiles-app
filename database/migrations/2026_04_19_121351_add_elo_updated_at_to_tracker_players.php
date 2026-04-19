<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track when the Poller-based ELO was last computed for each player.
 *
 * Used by the hybrid compute strategy:
 *   - Nightly cron bulk-recomputes every eligible player.
 *   - On profile view, if elo_updated_at is NULL or older than 24h,
 *     we recompute just that player on-the-fly and write back.
 *
 * This avoids the "1000 for everyone until the cron runs" problem while
 * keeping page-view latency low for repeat visits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_players', function (Blueprint $t) {
            $t->timestamp('elo_updated_at', 3)->nullable()->after('elo_peak');
            $t->index('elo_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_players', function (Blueprint $t) {
            $t->dropIndex(['elo_updated_at']);
            $t->dropColumn('elo_updated_at');
        });
    }
};
