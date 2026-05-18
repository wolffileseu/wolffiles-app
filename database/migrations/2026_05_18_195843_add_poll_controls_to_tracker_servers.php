<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->unsignedSmallInteger('custom_poll_interval')
                ->nullable()
                ->after('poll_failures')
                ->comment('Override calculated poll interval in seconds (15–3600). Applies to both online cadence and offline backoff.');
            $table->boolean('polling_paused')
                ->default(false)
                ->after('custom_poll_interval');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropColumn(['custom_poll_interval', 'polling_paused']);
        });
    }
};
