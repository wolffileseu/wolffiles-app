<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // "ET Server 12 Slots"
            $table->string('slug')->unique();
            $table->enum('game', ['et', 'etl', 'rtcw']);     // ET 2.60b, ET:Legacy, RtCW
            $table->unsignedSmallInteger('slots');            // 12, 16, 24, 32
            $table->unsignedInteger('memory_mb');             // RAM limit
            $table->unsignedSmallInteger('cpu_percent');      // CPU limit
            $table->unsignedInteger('disk_mb');               // Disk limit
            $table->decimal('price_daily', 8, 2);
            $table->decimal('price_weekly', 8, 2);
            $table->decimal('price_monthly', 8, 2);
            $table->decimal('price_quarterly', 8, 2);
            $table->text('description')->nullable();
            $table->json('features')->nullable();             // ["FastDL", "DDoS Protection", etc.]
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_products');
    }
};
