<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_elo_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('tracker_players')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('tracker_games');
            $table->decimal('elo_before', 8, 2);
            $table->decimal('elo_after', 8, 2);
            $table->decimal('change', 6, 2);
            $table->enum('reason', ['session', 'decay', 'adjustment', 'manual']);
            $table->timestamp('recorded_at');
            $table->index(['player_id', 'recorded_at']);
            $table->index('game_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_elo_history');
    }
};
