<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_server_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('tracker_servers')->cascadeOnDelete();
            $table->string('map', 100)->nullable();
            $table->integer('players')->default(0);
            $table->integer('max_players')->default(0);
            $table->string('gametype', 50)->nullable();
            $table->timestamp('polled_at');
            $table->index(['server_id', 'polled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_server_history');
    }
};
