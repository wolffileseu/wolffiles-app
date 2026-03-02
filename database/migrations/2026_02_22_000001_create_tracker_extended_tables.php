<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rankings / Leaderboard
        Schema::create('tracker_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('tracker_players')->cascadeOnDelete();
            $table->string('period', 20);       // daily, weekly, monthly, alltime
            $table->date('period_date');
            $table->integer('rank');
            $table->decimal('elo_rating', 8, 2)->default(1000);
            $table->integer('elo_change')->default(0);
            $table->bigInteger('total_xp')->default(0);
            $table->integer('playtime_minutes')->default(0);
            $table->integer('sessions_count')->default(0);
            $table->integer('kills')->default(0);
            $table->integer('deaths')->default(0);
            $table->integer('servers_played')->default(0);
            $table->integer('maps_played')->default(0);
            $table->timestamps();

            $table->unique(['player_id', 'period', 'period_date']);
            $table->index(['period', 'period_date', 'rank']);
        });

        // Server Ratings / Reviews
        Schema::create('tracker_server_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('tracker_servers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('rating')->unsigned();  // 1-5
            $table->text('comment')->nullable();
            $table->boolean('is_approved')->default(true);
            $table->timestamps();

            $table->unique(['server_id', 'user_id']);
            $table->index(['server_id', 'is_approved']);
        });

        // Match Finder / Scrims
        Schema::create('tracker_scrims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('clan_id')->nullable()->constrained('tracker_clans')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('game_type', 50);
            $table->string('map_preference')->nullable();
            $table->string('mod_preference')->nullable();
            $table->string('region', 50)->nullable();
            $table->string('skill_level', 20)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->enum('status', ['open', 'matched', 'playing', 'completed', 'cancelled'])->default('open');
            $table->string('contact_discord')->nullable();
            $table->string('server_ip')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });

        // Add missing columns to tracker_clans (if not there)
        if (!Schema::hasColumn('tracker_clans', 'description')) {
            Schema::table('tracker_clans', function (Blueprint $table) {
                $table->text('description')->nullable()->after('name');
                $table->string('discord')->nullable()->after('website');
                $table->integer('active_member_count')->default(0)->after('member_count');
                $table->foreignId('claimed_by_user_id')->nullable()->after('status');
                $table->boolean('is_verified')->default(false)->after('status');
            });
        }

        // Add user_id to tracker_players if missing (for claiming)
        if (!Schema::hasColumn('tracker_players', 'user_id')) {
            Schema::table('tracker_players', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('guid_hash');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_scrims');
        Schema::dropIfExists('tracker_server_ratings');
        Schema::dropIfExists('tracker_rankings');
    }
};
