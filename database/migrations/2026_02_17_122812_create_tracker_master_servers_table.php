<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_master_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('tracker_games')->cascadeOnDelete();
            $table->string('address');
            $table->integer('port')->default(27950);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_queried_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->integer('servers_found')->default(0);
            $table->integer('failures_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['game_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_master_servers');
    }
};
