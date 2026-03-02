<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_server_map_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('tracker_servers')->cascadeOnDelete();
            $table->string('map_name', 100);
            $table->integer('times_played')->default(0);
            $table->integer('total_time_minutes')->default(0);
            $table->timestamp('last_played_at')->nullable();
            $table->decimal('avg_players', 5, 2)->default(0);
            $table->integer('peak_players')->default(0);
            $table->timestamps();
            $table->unique(['server_id', 'map_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_server_map_stats');
    }
};
