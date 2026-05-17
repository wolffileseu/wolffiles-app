<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testservers', function (Blueprint $table) {
            $table->id();

            // Identifikation
            $table->string('name', 64);                  // "Testserver 1"
            $table->string('slug', 64)->unique();        // "testserver-1"
            $table->unsignedTinyInteger('slot_number')->unique();

            // Pterodactyl
            $table->uuid('pterodactyl_uuid');            // wie bei EttvSlot
            $table->unsignedBigInteger('pterodactyl_server_id')->nullable();
            $table->unsignedTinyInteger('pterodactyl_egg_id')->default(17); // ETLegacy

            // Connect-Info (die User sieht)
            $table->string('connect_ip', 64);            // 138.201.36.184 oder hostname
            $table->unsignedSmallInteger('connect_port');// 27970 etc

            // Defaults für Idle-State
            $table->string('default_mod', 32)->default('legacy');
            $table->string('default_map', 64)->default('oasis');
            $table->string('default_config', 64)->default('etl_server.cfg');

            // Limits & Konfig
            $table->unsignedSmallInteger('max_session_minutes')->default(20);
            $table->unsignedTinyInteger('max_players')->default(16);
            $table->boolean('enabled')->default(true);
            $table->boolean('public_visible')->default(true);

            // Live-Status (Cache)
            $table->enum('status', [
                'idle',         // Pool, niemand testet
                'reserving',    // Session wird gerade gestartet
                'active',       // Session läuft
                'cleanup',      // Session wird beendet, Server resetted
                'offline',      // Container down / Fehler
                'maintenance',  // Manuell deaktiviert
            ])->default('idle');
            $table->timestamp('last_status_check_at')->nullable();
            $table->text('last_error')->nullable();

            // Stats Cache (für schnelle Anzeige)
            $table->unsignedInteger('total_sessions')->default(0);
            $table->timestamp('last_session_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index(['enabled', 'public_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testservers');
    }
};
