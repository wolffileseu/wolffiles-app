<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // S3 path
            $table->string('thumbnail_path')->nullable(); // S3 thumbnail path
            $table->string('source')->default('manual'); // manual, extracted, levelshot
            $table->string('original_name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();

            $table->index(['file_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_screenshots');
    }
};
