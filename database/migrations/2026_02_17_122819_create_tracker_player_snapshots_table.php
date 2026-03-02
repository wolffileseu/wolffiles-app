<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_player_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('tracker_player_sessions')->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('tracker_servers')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('tracker_players')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->integer('score')->default(0);
            $table->integer('ping')->default(0);
            $table->string('team', 20)->nullable();
            $table->timestamp('polled_at');
            $table->index('session_id');
            $table->index(['player_id', 'polled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_player_snapshots');
    }
};
