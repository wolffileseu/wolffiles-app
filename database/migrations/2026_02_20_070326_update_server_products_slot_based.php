<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_products', function (Blueprint $table) {
            // Slot-basierte Preise
            $table->decimal('price_per_slot_daily', 8, 4)->after('price_quarterly')->default(0.0500);
            $table->decimal('price_per_slot_weekly', 8, 4)->after('price_per_slot_daily')->default(0.2500);
            $table->decimal('price_per_slot_monthly', 8, 4)->after('price_per_slot_weekly')->default(0.5000);
            $table->decimal('price_per_slot_quarterly', 8, 4)->after('price_per_slot_monthly')->default(1.2000);
            $table->unsignedSmallInteger('min_slots')->after('slots')->default(2);
            $table->unsignedSmallInteger('max_slots')->after('min_slots')->default(64);
            $table->decimal('memory_per_slot_mb', 8, 2)->after('memory_mb')->default(32.00);
            $table->decimal('cpu_per_slot_percent', 8, 2)->after('cpu_percent')->default(5.00);
            $table->decimal('disk_per_slot_mb', 8, 2)->after('disk_mb')->default(128.00);
            $table->unsignedInteger('base_memory_mb')->after('memory_per_slot_mb')->default(256);
            $table->unsignedInteger('base_disk_mb')->after('disk_per_slot_mb')->default(1024);
        });
    }

    public function down(): void
    {
        Schema::table('server_products', function (Blueprint $table) {
            $table->dropColumn([
                'price_per_slot_daily', 'price_per_slot_weekly',
                'price_per_slot_monthly', 'price_per_slot_quarterly',
                'min_slots', 'max_slots', 'memory_per_slot_mb',
                'cpu_per_slot_percent', 'disk_per_slot_mb',
                'base_memory_mb', 'base_disk_mb',
            ]);
        });
    }
};
