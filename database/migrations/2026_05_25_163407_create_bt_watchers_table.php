<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bt_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('bt_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('notify_status')->default(true);
            $table->boolean('notify_comments')->default(true);
            $table->boolean('notify_assignment')->default(true);
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bt_watchers');
    }
};
