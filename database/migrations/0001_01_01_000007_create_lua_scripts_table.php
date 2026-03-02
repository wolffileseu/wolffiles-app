<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lua_scripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_html')->nullable();
            $table->text('installation_guide')->nullable();

            // File
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('file_hash', 64)->nullable();
            $table->string('version', 50)->nullable();

            // Compatibility
            $table->json('compatible_mods')->nullable(); // ["etpub", "silent", "nitmod"]
            $table->json('dependencies')->nullable();
            $table->string('min_lua_version', 20)->nullable();

            // Status
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();

            // Stats
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            // Virus scan
            $table->boolean('virus_scanned')->default(false);
            $table->boolean('virus_clean')->nullable();

            $table->boolean('is_featured')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->fullText(['title', 'description']);
        });

        // LUA script screenshots
        Schema::create('lua_script_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lua_script_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lua_script_screenshots');
        Schema::dropIfExists('lua_scripts');
    }
};
