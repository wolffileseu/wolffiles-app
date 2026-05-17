<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testserver_loaded_maps', function (Blueprint $table) {
            // Echter BSP-Filename aus der PK3 (lowercase, ohne .bsp, ohne maps/-Prefix)
            // z.B. "(um)arena" für UMArena, "ae_wizerness" für Adlernest, etc.
            $table->string('bsp_name', 128)->nullable()->after('map_slug');
        });
    }

    public function down(): void
    {
        Schema::table('testserver_loaded_maps', function (Blueprint $table) {
            $table->dropColumn('bsp_name');
        });
    }
};
