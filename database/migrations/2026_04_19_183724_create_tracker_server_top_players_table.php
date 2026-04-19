<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_server_top_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->unsignedBigInteger('player_id');
            $table->unsignedTinyInteger('rank');
            $table->string('name_clean');
            $table->text('name_html')->nullable();
            $table->unsignedBigInteger('total_xp');
            $table->unsignedInteger('total_minutes');
            $table->timestamp('computed_at');

            $table->index(['server_id', 'rank']);
            $table->unique(['server_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_server_top_players');
    }
};
