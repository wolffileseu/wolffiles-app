<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testserver_mods', function (Blueprint $table) {
            $table->id();

            // Identifikation
            $table->string('slug', 32)->unique();        // legacy, nitmod, etpub
            $table->string('display_name', 64);          // "ET: Legacy", "NitMod 2.3.5"
            $table->string('short_description', 255)->nullable();

            // FastDL-Anbindung
            $table->string('fastdl_archive_path', 255)->nullable();
            // z.B. "mods/nitmod.zip" relativ zu dl.wolffiles.eu/et/
            // null = Mod ist bereits im Container (etmain, legacy)

            $table->string('fastdl_archive_sha256', 64)->nullable();
            // Optional: Hash für Integrity-Check vor Extract

            // Server-Config Defaults
            $table->string('default_config_file', 64)->default('etl_server.cfg');
            $table->string('fs_game_dir', 32);
            // Container-Ordner: legacy / nitmod / etpub / etmain

            // UI / Sichtbarkeit
            $table->boolean('enabled')->default(true);
            $table->boolean('show_on_public')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Optional: Mod-spezifische Hinweise
            $table->text('notes')->nullable();
            // z.B. "NitMod nutzt extra LUA-Module"

            // Engine-Kompatibilität (welche Eggs)
            $table->json('compatible_egg_ids')->nullable();
            // [17, 18] = ETLegacy + ET2.60b kompatibel

            $table->timestamps();

            $table->index(['enabled', 'show_on_public', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testserver_mods');
    }
};
