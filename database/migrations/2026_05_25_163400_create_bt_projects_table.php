<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bt_projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6366f1');
            $table->string('icon', 16)->nullable();
            $table->string('github_repo', 200)->nullable()->comment('e.g. wolffileseu/wolffiles-app');
            $table->boolean('github_sync_enabled')->default(false);
            $table->foreignId('default_assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('discord_webhook_url', 500)->nullable();
            $table->string('telegram_chat_id', 64)->nullable();
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bt_projects');
    }
};
