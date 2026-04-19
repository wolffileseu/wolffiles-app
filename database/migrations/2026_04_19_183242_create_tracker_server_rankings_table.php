<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("tracker_server_rankings", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("server_id");
            $table->unsignedInteger("game_id");
            $table->unsignedInteger("rank");
            $table->unsignedInteger("total_in_game");
            $table->decimal("avg_players_30d", 6, 2);
            $table->unsignedInteger("polls_counted");
            $table->timestamp("computed_at");

            $table->unique("server_id");
            $table->index(["game_id", "rank"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("tracker_server_rankings");
    }
};
