<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Wiki Categories
        if (!Schema::hasTable('wiki_categories')) {
            Schema::create('wiki_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->json('name_translations')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Wiki Articles
        if (!Schema::hasTable('wiki_articles')) {
            Schema::create('wiki_articles', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content');
                $table->text('excerpt')->nullable();
                $table->unsignedBigInteger('wiki_category_id')->nullable();
                $table->unsignedBigInteger('user_id'); // author
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('status')->default('draft'); // draft, pending, published, archived
                $table->json('tags')->nullable();
                $table->json('title_translations')->nullable();
                $table->unsignedInteger('view_count')->default(0);
                $table->unsignedInteger('revision_count')->default(0);
                $table->boolean('is_locked')->default(false); // prevent edits
                $table->boolean('is_featured')->default(false);
                $table->json('attachments')->nullable(); // PDFs, docs on S3
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'published_at']);
                $table->index('wiki_category_id');
                $table->index('user_id');
            });
        }

        // Wiki Revisions (version history)
        if (!Schema::hasTable('wiki_revisions')) {
            Schema::create('wiki_revisions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('wiki_article_id');
                $table->unsignedBigInteger('user_id'); // who edited
                $table->string('title');
                $table->longText('content');
                $table->text('change_summary')->nullable();
                $table->unsignedInteger('revision_number');
                $table->timestamps();

                $table->index(['wiki_article_id', 'revision_number']);
            });
        }

        // Wiki Article Media (images/videos in articles)
        if (!Schema::hasTable('wiki_media')) {
            Schema::create('wiki_media', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('wiki_article_id')->nullable();
                $table->unsignedBigInteger('tutorial_id')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->string('path'); // S3 path
                $table->string('filename');
                $table->string('mime_type');
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('type')->default('image'); // image, video, document
                $table->text('caption')->nullable();
                $table->timestamps();

                $table->index('wiki_article_id');
                $table->index('tutorial_id');
            });
        }

        // Tutorial Categories
        if (!Schema::hasTable('tutorial_categories')) {
            Schema::create('tutorial_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->json('name_translations')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Tutorials
        if (!Schema::hasTable('tutorials')) {
            Schema::create('tutorials', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('content'); // main content (rich editor)
                $table->text('excerpt')->nullable();
                $table->unsignedBigInteger('tutorial_category_id')->nullable();
                $table->unsignedBigInteger('user_id'); // author
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->string('status')->default('draft'); // draft, pending, published, archived
                $table->string('difficulty')->default('beginner'); // beginner, intermediate, advanced
                $table->unsignedInteger('estimated_minutes')->nullable();
                $table->text('prerequisites')->nullable();
                $table->json('tags')->nullable();
                $table->json('title_translations')->nullable();
                $table->string('youtube_url')->nullable();
                $table->string('video_path')->nullable(); // S3 path for uploaded video
                $table->json('attachments')->nullable(); // PDFs, project files on S3
                $table->unsignedInteger('view_count')->default(0);
                $table->unsignedInteger('helpful_count')->default(0);
                $table->unsignedInteger('not_helpful_count')->default(0);
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_series')->default(false);
                $table->unsignedBigInteger('series_parent_id')->nullable(); // for multi-part tutorials
                $table->unsignedInteger('series_order')->default(0);
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'published_at']);
                $table->index('tutorial_category_id');
                $table->index('user_id');
                $table->index('difficulty');
                $table->index('series_parent_id');
            });
        }

        // Tutorial Steps (optional step-by-step)
        if (!Schema::hasTable('tutorial_steps')) {
            Schema::create('tutorial_steps', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tutorial_id');
                $table->unsignedInteger('step_number');
                $table->string('title');
                $table->longText('content');
                $table->string('image_path')->nullable(); // S3 path
                $table->string('video_url')->nullable();
                $table->text('tip')->nullable(); // pro tip for this step
                $table->timestamps();

                $table->index(['tutorial_id', 'step_number']);
            });
        }

        // Helpful votes for tutorials
        if (!Schema::hasTable('tutorial_votes')) {
            Schema::create('tutorial_votes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tutorial_id');
                $table->unsignedBigInteger('user_id');
                $table->boolean('is_helpful');
                $table->timestamps();

                $table->unique(['tutorial_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorial_votes');
        Schema::dropIfExists('tutorial_steps');
        Schema::dropIfExists('tutorials');
        Schema::dropIfExists('tutorial_categories');
        Schema::dropIfExists('wiki_media');
        Schema::dropIfExists('wiki_revisions');
        Schema::dropIfExists('wiki_articles');
        Schema::dropIfExists('wiki_categories');
    }
};
