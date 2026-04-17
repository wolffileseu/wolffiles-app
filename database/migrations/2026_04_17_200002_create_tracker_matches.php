<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tracker_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('tracker_servers');

            // Match identity
            $table->string('map_name', 64)->index();
            $table->timestamp('started_at', 3)->index();   // millisecond precision
            $table->timestamp('ended_at', 3)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();

            // How this match concluded. Values:
            //   'mapend'      — clean completion (objectives finished or timelimit)
            //   'maprestart'  — round restart within same map
            //   'mapchange'   — admin/vote changed map
            //   'disconnect'  — server stopped mid-match
            //   'timeout'     — no events for N minutes, we assumed it's over
            $table->string('end_reason', 32)->nullable();

            // Denormalized aggregates for quick listing
            $table->unsignedSmallInteger('player_count_max')->default(0);
            $table->decimal('player_count_avg', 4, 1)->default(0.0);
            $table->unsignedSmallInteger('total_kills')->default(0);
            $table->unsignedSmallInteger('total_deaths')->default(0);

            // Snapshot of the last scoreboard state for fast recap display
            $table->json('final_scoreboard')->nullable();

            $table->timestamps();

            $table->index(['server_id', 'started_at']);
            $table->index(['server_id', 'ended_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_matches');
    }
};
