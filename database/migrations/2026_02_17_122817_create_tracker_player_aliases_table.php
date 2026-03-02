<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_player_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('tracker_players')->cascadeOnDelete();
            $table->string('name');
            $table->string('name_clean');
            $table->text('name_html')->nullable();
            $table->integer('times_used')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->index('player_id');
            $table->index('name_clean');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_player_aliases');
    }
};
