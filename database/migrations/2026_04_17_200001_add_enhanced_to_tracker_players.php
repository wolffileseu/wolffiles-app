<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_players', function (Blueprint $table) {
            // Marks players that have at least one enhanced-data session.
            // Used to conditionally render the "Enhanced Stats" panel on profile pages.
            $table->boolean('has_enhanced_data')->default(false)->after('status')->index();
            $table->timestamp('enhanced_first_seen_at')->nullable()->after('has_enhanced_data');
            $table->timestamp('enhanced_last_seen_at')->nullable()->after('enhanced_first_seen_at');
            // Aggregates refreshed by a scheduled job — quick reads without recomputing per request.
            $table->unsignedBigInteger('enhanced_total_kills')->default(0)->after('enhanced_last_seen_at');
            $table->unsignedBigInteger('enhanced_total_deaths')->default(0)->after('enhanced_total_kills');
            $table->unsignedBigInteger('enhanced_total_headshots')->default(0)->after('enhanced_total_deaths');
            $table->unsignedBigInteger('enhanced_total_damage')->default(0)->after('enhanced_total_headshots');
            $table->unsignedInteger('enhanced_match_count')->default(0)->after('enhanced_total_damage');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_players', function (Blueprint $table) {
            $table->dropIndex(['has_enhanced_data']);
            $table->dropColumn([
                'has_enhanced_data',
                'enhanced_first_seen_at',
                'enhanced_last_seen_at',
                'enhanced_total_kills',
                'enhanced_total_deaths',
                'enhanced_total_headshots',
                'enhanced_total_damage',
                'enhanced_match_count',
            ]);
        });
    }
};
