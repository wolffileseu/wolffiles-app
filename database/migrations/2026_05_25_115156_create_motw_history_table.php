<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('motw_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->timestamp('featured_at');
            $table->string('strategy', 32)->nullable();
            $table->string('game', 32)->nullable();
            
            $table->index('file_id');
            $table->index('featured_at');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motw_history');
    }
};
