<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_player_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('tracker_players')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('tracker_games');
            $table->date('date');
            $table->integer('play_time_minutes')->default(0);
            $table->integer('sessions')->default(0);
            $table->integer('kills')->default(0);
            $table->integer('deaths')->default(0);
            $table->integer('xp')->default(0);
            $table->integer('servers_played')->default(0);
            $table->integer('maps_played')->default(0);
            $table->unique(['player_id', 'date', 'game_id']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_player_daily_stats');
    }
};
