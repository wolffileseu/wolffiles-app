<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallpapers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image_path');
            $table->json('display_areas')->nullable();
            $table->string('overlay_color', 7)->default('#111827');
            $table->unsignedTinyInteger('overlay_opacity')->default(70);
            $table->unsignedTinyInteger('overlay_blur')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallpapers');
    }
};
