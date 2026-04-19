<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_server_rankings', function (Blueprint $table) {
            $table->unsignedInteger('peak_players_30d')->default(0)->after('avg_players_30d');
            $table->unsignedInteger('total_polls_30d')->default(0)->after('polls_counted');
            $table->unsignedInteger('online_polls_30d')->default(0)->after('total_polls_30d');
            $table->unsignedBigInteger('total_playtime_minutes_30d')->default(0)->after('online_polls_30d');
            $table->unsignedInteger('unique_players_30d')->default(0)->after('total_playtime_minutes_30d');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_server_rankings', function (Blueprint $table) {
            $table->dropColumn([
                'peak_players_30d',
                'total_polls_30d',
                'online_polls_30d',
                'total_playtime_minutes_30d',
                'unique_players_30d',
            ]);
        });
    }
};
