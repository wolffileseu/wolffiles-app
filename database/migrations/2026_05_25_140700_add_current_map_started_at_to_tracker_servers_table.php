<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->timestamp('current_map_started_at')
                  ->nullable()
                  ->after('current_map')
                  ->comment('Wall-clock time when the current map began on this server');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropColumn('current_map_started_at');
        });
    }
};
