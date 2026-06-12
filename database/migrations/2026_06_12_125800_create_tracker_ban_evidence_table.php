<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_ban_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ban_id')->constrained('tracker_bans')->cascadeOnDelete();
            $table->enum('type', ['screenshot', 'demo', 'video', 'link']);
            $table->string('file_path', 512)->nullable();   // S3 path (screenshot/demo)
            $table->string('external_url', 512)->nullable(); // link/video
            $table->string('caption', 255)->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('server_id')->nullable()->constrained('tracker_servers')->nullOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['ban_id', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_ban_evidence');
    }
};
