<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bt_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('bt_tasks')->cascadeOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained('bt_comments')->cascadeOnDelete();
            $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_filename', 255);
            $table->string('stored_path', 500)->comment('S3 object key or local path');
            $table->string('disk', 32)->default('hetzner');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum_sha256', 64)->nullable();
            $table->timestamps();
            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bt_attachments');
    }
};
