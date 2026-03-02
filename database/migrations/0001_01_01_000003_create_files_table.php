<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            // Basic info
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_html')->nullable();
            $table->json('title_translations')->nullable();
            $table->json('description_translations')->nullable();

            // File info
            $table->string('file_path'); // S3 path
            $table->string('file_name'); // Original filename
            $table->string('file_extension', 20);
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('file_hash', 64)->nullable(); // SHA-256
            $table->string('mime_type')->nullable();

            // Extracted metadata
            $table->string('map_name')->nullable(); // BSP name from PK3
            $table->string('game', 50)->nullable(); // ET, RtCW, etc.
            $table->string('mod_compatibility')->nullable(); // ETPub, Silent, etc.
            $table->string('version', 50)->nullable();
            $table->string('original_author')->nullable();
            $table->text('readme_content')->nullable();
            $table->json('extracted_metadata')->nullable(); // Raw metadata JSON

            // Status
            $table->string('status')->default('pending'); // pending, approved, rejected, archived
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();

            // Stats
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            // Featured
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_at')->nullable();
            $table->string('featured_label')->nullable(); // "Map of the Week"

            // Virus scan
            $table->boolean('virus_scanned')->default(false);
            $table->boolean('virus_clean')->nullable();
            $table->text('virus_scan_result')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index(['game', 'status']);
            $table->index('download_count');
            $table->index('average_rating');
            $table->index('map_name');
            $table->fullText(['title', 'description', 'map_name', 'original_author']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
