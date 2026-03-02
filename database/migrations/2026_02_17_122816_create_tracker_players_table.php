<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_players', function (Blueprint $table) {
            $table->id();
            $table->string('guid_hash', 64);
            $table->string('name')->nullable();
            $table->string('name_clean')->nullable();
            $table->text('name_html')->nullable();
            $table->string('country', 100)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->integer('total_play_time_minutes')->default(0);
            $table->integer('total_kills')->default(0);
            $table->integer('total_deaths')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->bigInteger('total_xp')->default(0);
            $table->decimal('elo_rating', 8, 2)->default(1000.00);
            $table->decimal('elo_peak', 8, 2)->default(1000.00);
            $table->integer('elo_games')->default(0);
            $table->integer('level')->default(0);
            $table->enum('status', ['active', 'banned', 'hidden'])->default('active');
            $table->timestamps();
            $table->index('guid_hash');
            $table->index('name_clean');
            $table->index(['elo_rating', 'id']);
            $table->index('last_seen_at');
            $table->index('country_code');
            $table->index('total_play_time_minutes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_players');
    }
};
