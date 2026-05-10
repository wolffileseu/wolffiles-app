<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')
                ->constrained('pm_messages')
                ->cascadeOnDelete();
            $table->enum('type', ['image'])->default('image')
                ->comment('Future: video, audio, file');
            $table->string('storage_disk', 50)->default('s3');
            $table->string('storage_path', 500)
                ->comment('Object key in Hetzner S3 bucket');
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamp('purged_at')->nullable()
                ->comment('When retention deleted file from storage');
            $table->timestamps();

            $table->index('message_id');
            $table->index('purged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_message_attachments');
    }
};
