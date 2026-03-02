<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ratings
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->timestamps();

            $table->unique(['user_id', 'file_id']);
            $table->index(['file_id', 'rating']);
        });

        // Comments
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable'); // file, post, lua_script
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id', 'is_approved']);
        });

        // Tags
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('general'); // general, game, mod, feature
            $table->json('name_translations')->nullable();
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable');

            $table->unique(['tag_id', 'taggable_type', 'taggable_id']);
        });

        // Pages (Custom Pages System)
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('title_translations')->nullable();
            $table->longText('content')->nullable();
            $table->json('content_translations')->nullable();
            $table->string('type')->default('richtext'); // richtext, html, markdown, pdf
            $table->string('template')->nullable(); // Optional blade template
            $table->string('pdf_path')->nullable(); // S3 path for PDF
            $table->boolean('is_published')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('meta')->nullable(); // SEO meta, custom fields
            $table->timestamps();
            $table->softDeletes();
        });

        // Menus
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "main", "footer"
            $table->string('location')->unique(); // header, footer, sidebar
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('title');
            $table->json('title_translations')->nullable();
            $table->string('url')->nullable(); // external URL
            $table->string('route')->nullable(); // Laravel route name
            $table->nullableMorphs('linkable'); // page, category, etc.
            $table->string('target', 20)->default('_self');
            $table->string('icon')->nullable();
            $table->string('css_class')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'sort_order']);
        });

        // Downloads log
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->timestamps();

            $table->index(['file_id', 'created_at']);
            $table->index(['created_at']);
        });

        // Favorites
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'file_id']);
        });

        // Reports (flag problematic files)
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reportable');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, resolved, dismissed
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('ratings');
    }
};
