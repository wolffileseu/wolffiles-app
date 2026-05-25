<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bt_task_tag', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained('bt_tasks')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('bt_tags')->cascadeOnDelete();
            $table->primary(['task_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bt_task_tag');
    }
};
