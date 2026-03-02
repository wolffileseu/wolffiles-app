<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_player_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('tracker_players')->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('tracker_servers')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('tracker_games');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->string('map_name', 100)->nullable();
            $table->integer('kills')->default(0);
            $table->integer('deaths')->default(0);
            $table->integer('xp')->default(0);
            $table->integer('score')->default(0);
            $table->string('team', 20)->nullable();
            $table->index(['player_id', 'started_at']);
            $table->index('server_id');
            $table->index('game_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_player_sessions');
    }
};
