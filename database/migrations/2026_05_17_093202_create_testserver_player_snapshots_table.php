<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testserver_player_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('testserver_session_id')
                ->constrained('testserver_sessions')
                ->cascadeOnDelete();

            $table->timestamp('snapshot_at')->nullable();

            $table->unsignedTinyInteger('player_count')->default(0);
            $table->json('player_names')->nullable();
            $table->json('player_scores')->nullable();

            $table->string('current_map', 64)->nullable();
            $table->string('current_mod', 32)->nullable();
            $table->unsignedSmallInteger('ping_ms')->nullable();

            // Indexes mit kurzen Namen (MySQL Limit = 64 chars)
            $table->index(
                ['testserver_session_id', 'snapshot_at'],
                'tps_session_time_idx'
            );
            $table->index('snapshot_at', 'tps_snapshot_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testserver_player_snapshots');
    }
};
