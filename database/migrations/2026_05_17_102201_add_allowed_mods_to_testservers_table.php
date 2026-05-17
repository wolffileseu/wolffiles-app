<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testservers', function (Blueprint $table) {
            // JSON-Array mit Mod-Slugs die auf diesem Server erlaubt sind
            // null oder leeres Array = alle aktivierten Mods erlaubt
            $table->json('allowed_mod_slugs')->nullable()->after('default_mod');
        });
    }

    public function down(): void
    {
        Schema::table('testservers', function (Blueprint $table) {
            $table->dropColumn('allowed_mod_slugs');
        });
    }
};
