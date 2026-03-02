<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_html')->nullable();

            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_extension', 20)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('file_hash', 64)->nullable();
            $table->string('mime_type')->nullable();

            $table->string('game', 50)->default('ET');
            $table->string('map_name', 100)->nullable();
            $table->string('mod_name', 50)->nullable();
            $table->string('gametype', 50)->nullable();
            $table->string('match_format', 20)->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('demo_format', 20)->nullable();

            $table->string('team_axis', 100)->nullable();
            $table->string('team_allies', 100)->nullable();
            $table->date('match_date')->nullable();
            $table->string('match_source')->nullable();
            $table->string('match_source_url')->nullable();
            $table->string('recorder_name', 100)->nullable();
            $table->string('server_name')->nullable();

            $table->json('player_list')->nullable();

            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            $table->boolean('is_featured')->default(false);
            $table->string('featured_label')->nullable();

            $table->boolean('virus_scanned')->default(false);
            $table->boolean('virus_clean')->nullable();
            $table->text('virus_scan_result')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index(['game', 'status']);
            $table->index('download_count');
            $table->index('map_name');
            $table->index('mod_name');
            $table->index('match_date');
            $table->index('demo_format');
            $table->fullText(['title', 'description', 'map_name', 'team_axis', 'team_allies', 'recorder_name'], 'demos_fulltext');
        });

        Schema::create('demo_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demo_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_screenshots');
        Schema::dropIfExists('demos');
    }
};
