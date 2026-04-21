<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            // Round-trip time in milliseconds from our tracker (web01) to the game server.
            // NULL when server is offline or latency could not be measured.
            $table->unsignedSmallInteger('latency_ms')->nullable()->after('os');

            // sv_privateClients from getstatus — reserved slots not visible to public players.
            // Displayed as "22+11/60" where 11 = private slots.
            $table->unsignedSmallInteger('private_slots')->nullable()->after('max_players');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropColumn(['latency_ms', 'private_slots']);
        });
    }
};
