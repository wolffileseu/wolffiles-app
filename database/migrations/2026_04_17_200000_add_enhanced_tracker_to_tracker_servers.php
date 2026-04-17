<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            // Marks servers that push data via sv_tracker2 UDP stream (Enhanced Tracker).
            $table->boolean('is_enhanced_tracker')->default(false)->after('status')->index();
            $table->timestamp('enhanced_first_seen_at')->nullable()->after('is_enhanced_tracker');
            $table->timestamp('enhanced_last_event_at')->nullable()->after('enhanced_first_seen_at');
            $table->unsignedBigInteger('enhanced_event_count')->default(0)->after('enhanced_last_event_at');
            // Remember the source IP we first accepted packets from — acts as trivial auth anchor.
            $table->string('enhanced_source_ip', 45)->nullable()->after('enhanced_event_count')->index();
            // Optional admin switch to blacklist a server from enhanced tracking even if it sends.
            $table->boolean('enhanced_disabled')->default(false)->after('enhanced_source_ip');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropIndex(['is_enhanced_tracker']);
            $table->dropIndex(['enhanced_source_ip']);
            $table->dropColumn([
                'is_enhanced_tracker',
                'enhanced_first_seen_at',
                'enhanced_last_event_at',
                'enhanced_event_count',
                'enhanced_source_ip',
                'enhanced_disabled',
            ]);
        });
    }
};
