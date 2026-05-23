<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            // Redundant with existing enhanced_last_event_at — drop it.
            if (Schema::hasColumn('tracker_servers', 'last_enhanced_event_at')) {
                $table->dropIndex(['last_enhanced_event_at']);
                $table->dropColumn('last_enhanced_event_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->timestamp('last_enhanced_event_at')->nullable()->index();
        });
    }
};
