<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // #18 Activity Log
        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action');
                $table->nullableMorphs('subject');
                $table->json('properties')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
                $table->index(['action', 'created_at']);
                $table->index('created_at');
            });
        }

        // #19 Notifications table
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // #20 User achievements pivot
        if (Schema::hasTable('badges') && !Schema::hasTable('badge_user')) {
            Schema::create('badge_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('badge_id');
                $table->timestamp('earned_at')->useCurrent();
                $table->timestamps();
                $table->unique(['user_id', 'badge_id']);
            });
        }

        // #27 Download statistics
        if (!Schema::hasTable('download_stats')) {
            Schema::create('download_stats', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('file_id');
                $table->date('date');
                $table->unsignedInteger('count')->default(0);
                $table->timestamps();
                $table->unique(['file_id', 'date']);
                $table->index('date');
            });
        }

        // #31 Trending score
        if (!Schema::hasColumn('files', 'trending_score')) {
            Schema::table('files', function (Blueprint $table) {
                $table->decimal('trending_score', 10, 4)->default(0);
            });
        }

        // #32 Rating criteria
        if (!Schema::hasTable('rating_criteria')) {
            Schema::create('rating_criteria', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->json('name_translations')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('file_criteria_ratings')) {
            Schema::create('file_criteria_ratings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('file_id');
                $table->unsignedBigInteger('rating_criteria_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedTinyInteger('score');
                $table->timestamps();
                $table->unique(['file_id', 'rating_criteria_id', 'user_id'], 'fcr_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('file_criteria_ratings');
        Schema::dropIfExists('rating_criteria');
        Schema::dropIfExists('download_stats');
        Schema::dropIfExists('badge_user');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('activity_logs');

        if (Schema::hasColumn('files', 'trending_score')) {
            Schema::table('files', function (Blueprint $table) {
                $table->dropColumn('trending_score');
            });
        }
    }
};