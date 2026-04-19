<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_player_rankings_30d', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('game_id');
            $table->unsignedInteger('rank');
            $table->unsignedInteger('total_in_game');
            $table->unsignedBigInteger('playtime_minutes_30d')->default(0);
            $table->unsignedInteger('sessions_count_30d')->default(0);
            $table->unsignedInteger('kills_30d')->default(0);
            $table->unsignedInteger('deaths_30d')->default(0);
            $table->unsignedBigInteger('xp_30d')->default(0);
            $table->unsignedInteger('unique_servers_30d')->default(0);
            $table->unsignedInteger('unique_maps_30d')->default(0);
            $table->integer('elo_rating')->nullable();
            $table->timestamp('computed_at');

            $table->unique(['player_id', 'game_id']);
            $table->index(['game_id', 'rank']);
            $table->index(['game_id', 'playtime_minutes_30d']);
            $table->index(['game_id', 'kills_30d']);
            $table->index(['game_id', 'xp_30d']);
            $table->index(['game_id', 'elo_rating']);

            $table->foreign('player_id')->references('id')->on('tracker_players')->cascadeOnDelete();
            $table->foreign('game_id')->references('id')->on('tracker_games')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_player_rankings_30d');
    }
};
