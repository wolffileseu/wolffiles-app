<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_matches', function (Blueprint $table) {
            $table->smallInteger('players_at_start')->unsigned()->nullable()->after('player_count_avg');
            $table->smallInteger('players_at_end')->unsigned()->nullable()->after('players_at_start');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_matches', function (Blueprint $table) {
            $table->dropColumn(['players_at_start', 'players_at_end']);
        });
    }
};
