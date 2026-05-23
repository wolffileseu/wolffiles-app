<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            // sv_tracker enhanced events — set by tracker-listener on every accepted enhanced event
            $table->timestamp('last_enhanced_event_at')->nullable()->index();

            // Normalized engine info — populated by EngineVersionParser on every poll
            $table->string('engine_family', 32)->nullable()->index();
            $table->string('engine_version', 64)->nullable()->index();
            $table->string('engine_platform', 64)->nullable();
            $table->date('engine_build_date')->nullable();
            $table->boolean('engine_is_dev_build')->default(false);
            $table->string('engine_display', 128)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropColumn([
                'last_enhanced_event_at',
                'engine_family',
                'engine_version',
                'engine_platform',
                'engine_build_date',
                'engine_is_dev_build',
                'engine_display',
            ]);
        });
    }
};
