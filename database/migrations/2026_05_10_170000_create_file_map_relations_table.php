<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('file_map_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('bot_file_id')->constrained('files')->cascadeOnDelete();
            $table->string('relation_type', 32)->default('bot_files')->comment('bot_files, waypoints, goals');
            $table->decimal('confidence', 4, 3)->default(1.000)->comment('0.000-1.000, 1.000 = manual');
            $table->string('source', 32)->default('auto')->comment('auto, manual, archive_scan');
            $table->boolean('is_manual')->default(false)->comment('protect from backfill overwrite');
            $table->timestamps();

            $table->unique(['map_file_id', 'bot_file_id'], 'fmr_unique');
            $table->index('bot_file_id');
            $table->index(['relation_type', 'confidence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_map_relations');
    }
};
