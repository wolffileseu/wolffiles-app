<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_clan_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clan_id')->constrained('tracker_clans')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('tracker_players')->cascadeOnDelete();
            $table->enum('role', ['member', 'officer', 'leader', 'founder'])->default('member');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique(['clan_id', 'player_id', 'joined_at']);
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_clan_members');
    }
};
