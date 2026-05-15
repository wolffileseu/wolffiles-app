<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('missing_textures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $t->string('texture_path', 500);
            $t->string('game', 50)->nullable();
            $t->unsignedInteger('request_count')->default(1);
            $t->timestamp('first_seen_at')->nullable();
            $t->timestamp('last_seen_at')->nullable();
            $t->boolean('resolved')->default(false);
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['file_id', 'texture_path']);
            $t->index(['resolved', 'request_count']);
            $t->index('game');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missing_textures');
    }
};
