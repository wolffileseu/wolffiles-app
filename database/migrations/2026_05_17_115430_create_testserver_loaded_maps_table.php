<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testserver_loaded_maps', function (Blueprint $table) {
            $table->id();

            // Welcher Server hat welche Map geladen
            $table->foreignId('testserver_id')
                ->constrained('testservers')
                ->cascadeOnDelete();

            // Map-Identifikation
            $table->string('map_slug', 128);           // 'umarena', 'goldrush-ettr'
            $table->foreignId('file_id')->nullable()
                ->constrained('files')
                ->nullOnDelete();                       // wenn aus Wolffiles-DB

            // Was wurde in den Container gelegt
            $table->json('pk3_filenames');             // ['(UM)Arena.pk3'] - mehrere möglich
            $table->unsignedBigInteger('total_bytes')->default(0);

            // Tracking
            $table->timestamp('loaded_at')->useCurrent();
            $table->unsignedInteger('use_count')->default(1);   // wie oft genutzt
            $table->timestamp('last_used_at')->nullable();

            // Status
            $table->enum('source', ['s3', 'vanilla', 'manual'])->default('s3');
            // vanilla = Map ist in pak1.pk3 oder pak2.pk3 schon drin (nicht extra geladen)

            $table->timestamps();

            // EIN Server kann nur EINE Map geladen haben → Unique
            $table->unique('testserver_id', 'tslm_server_unique');
            $table->index('map_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testserver_loaded_maps');
    }
};
