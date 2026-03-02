<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('tracker_games');
            $table->string('ip', 45);
            $table->integer('port')->default(27960);
            $table->string('hostname')->nullable();
            $table->string('hostname_clean')->nullable();
            $table->text('hostname_html')->nullable();
            $table->string('country', 100)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('current_map', 100)->nullable();
            $table->integer('current_players')->default(0);
            $table->integer('max_players')->default(0);
            $table->string('gametype', 50)->nullable();
            $table->string('mod_name', 100)->nullable();
            $table->string('mod_version', 50)->nullable();
            $table->boolean('is_private')->default(false);
            $table->boolean('needs_password')->default(false);
            $table->string('os', 50)->nullable();
            $table->boolean('sv_pure')->nullable();
            $table->boolean('punkbuster')->nullable();
            $table->boolean('is_ranked')->default(true);
            $table->boolean('is_online')->default(false);
            $table->boolean('is_manually_added')->default(false);
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'inactive', 'removed'])->default('active');
            $table->integer('total_players_tracked')->default(0);
            $table->integer('total_unique_players')->default(0);
            $table->decimal('uptime_percentage', 5, 2)->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_poll_at')->nullable();
            $table->integer('poll_failures')->default(0);
            $table->timestamps();
            $table->unique(['ip', 'port']);
            $table->index(['game_id', 'is_online']);
            $table->index('country_code');
            $table->index('current_players');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_servers');
    }
};
