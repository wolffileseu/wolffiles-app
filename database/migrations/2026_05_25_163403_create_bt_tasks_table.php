<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bt_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('bt_projects')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('bt_categories')->nullOnDelete();
            $table->unsignedInteger('task_number')->comment('Per-project ascending number, e.g. #42');
            $table->string('title', 255);
            $table->longText('description')->comment('Markdown');

            $table->string('status', 20)->default('new');
            $table->string('priority', 20)->default('normal');
            $table->string('severity', 20)->default('minor');
            $table->string('type', 20)->default('bug');

            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_name', 120)->nullable()->comment('Fallback for anonymous/Discord reporters');
            $table->string('reporter_email', 120)->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('affected_version', 50)->nullable();
            $table->string('target_version', 50)->nullable();

            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->date('due_date')->nullable();

            $table->unsignedInteger('github_issue_number')->nullable();
            $table->string('github_issue_url', 500)->nullable();
            $table->timestamp('github_last_synced_at')->nullable();

            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();

            $table->unique(['project_id', 'task_number']);
            $table->index(['project_id', 'status']);
            $table->index(['assignee_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bt_tasks');
    }
};
