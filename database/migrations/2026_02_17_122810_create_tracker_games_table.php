<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_games', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('short_name', 20);
            $table->integer('protocol_version');
            $table->integer('default_port')->default(27960);
            $table->string('query_type', 20)->default('quake3');
            $table->string('icon')->nullable();
            $table->string('color', 7)->default('#FF6600');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_games');
    }
};
