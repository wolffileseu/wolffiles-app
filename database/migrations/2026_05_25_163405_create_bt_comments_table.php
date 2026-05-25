<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bt_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('bt_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_name', 120)->nullable()->comment('Fallback for anonymous/Discord');
            $table->longText('body')->comment('Markdown');
            $table->boolean('is_internal')->default(false)->comment('Visible only to admins');
            $table->unsignedBigInteger('github_comment_id')->nullable();
            $table->timestamp('github_last_synced_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->index(['task_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bt_comments');
    }
};
