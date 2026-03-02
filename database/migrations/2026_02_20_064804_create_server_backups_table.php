<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('server_orders')->cascadeOnDelete();
            $table->string('name');
            $table->string('filename');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('s3_path')->nullable();
            $table->string('pterodactyl_backup_id')->nullable();
            $table->enum('type', ['auto', 'manual'])->default('auto');
            $table->enum('status', ['pending', 'completed', 'failed', 'restoring'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_backups');
    }
};
