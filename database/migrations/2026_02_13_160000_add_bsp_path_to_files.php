<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('bsp_path')->nullable()->after('file_path')
                  ->comment('S3 path to extracted BSP file for 3D preview');
        });

        // Add index for quick lookups
        Schema::table('files', function (Blueprint $table) {
            $table->index('bsp_path');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['bsp_path']);
            $table->dropColumn('bsp_path');
        });
    }
};
