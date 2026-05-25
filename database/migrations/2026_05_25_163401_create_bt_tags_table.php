<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bt_tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 50);
            $table->string('color', 7)->default('#94a3b8');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bt_tags');
    }
};
