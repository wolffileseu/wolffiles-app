<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_maps', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_clean', 100);
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->integer('total_servers')->default(0);
            $table->integer('total_time_played_minutes')->default(0);
            $table->integer('total_unique_players')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->integer('peak_concurrent_players')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('screenshot_path')->nullable();
            $table->timestamps();
            $table->unique('name_clean');
            $table->index('total_servers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_maps');
    }
};
