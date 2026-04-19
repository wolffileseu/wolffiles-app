<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Composite index for 30d aggregation queries (WHERE polled_at >= X GROUP BY server_id).
        // Existing index is (server_id, polled_at) which is wrong order for this query.
        $exists = collect(DB::select("SHOW INDEXES FROM tracker_server_history WHERE Key_name = 'tracker_server_history_polled_at_server_id_index'"))->isNotEmpty();
        if (! $exists) {
            Schema::table('tracker_server_history', function (Blueprint $table) {
                $table->index(['polled_at', 'server_id'], 'tracker_server_history_polled_at_server_id_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tracker_server_history', function (Blueprint $table) {
            $table->dropIndex('tracker_server_history_polled_at_server_id_index');
        });
    }
};
