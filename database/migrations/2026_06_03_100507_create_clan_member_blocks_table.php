<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clan_member_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clan_id')->constrained('clans')->cascadeOnDelete();
            $table->enum('block_type', ['player_id', 'name']);
            $table->foreignId('target_player_id')->nullable()->constrained('tracker_players')->cascadeOnDelete();
            $table->string('target_name', 255)->nullable();
            $table->foreignId('blocked_by_user_id')->constrained('users');
            $table->text('reason')->nullable();
            $table->timestamps();

            // Unique pro Clan + Target (NULL ist nicht unique in MySQL, daher OK dass beide Spalten nullable sind)
            $table->unique(['clan_id', 'target_player_id'], 'unique_clan_player_block');
            $table->unique(['clan_id', 'target_name'], 'unique_clan_name_block');

            // Lookup-Index für den Block-Check beim Auto-Pool
            $table->index(['clan_id', 'block_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clan_member_blocks');
    }
};
