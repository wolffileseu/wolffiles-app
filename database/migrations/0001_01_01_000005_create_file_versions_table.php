<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->string('version', 50);
            $table->string('file_path'); // S3 path
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('file_hash', 64)->nullable();
            $table->text('changelog')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();

            $table->index(['file_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_versions');
    }
};
