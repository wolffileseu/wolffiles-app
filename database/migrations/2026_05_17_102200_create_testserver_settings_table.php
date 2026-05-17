<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testserver_settings', function (Blueprint $table) {
            $table->id();

            // ──────────────────────────────────────
            // FEATURE TOGGLES
            // ──────────────────────────────────────
            $table->boolean('feature_enabled')->default(true);
            // Kill-switch: wenn off, ist /testserver/launch komplett deaktiviert

            $table->boolean('public_visible')->default(true);
            // Sidebar-Button auf Map-Pages zeigen?

            $table->boolean('require_login')->default(false);
            // Vorbereitet für später: Login-Zwang

            // ──────────────────────────────────────
            // CLOUDFLARE TURNSTILE (vorbereitet, default off)
            // ──────────────────────────────────────
            $table->boolean('turnstile_enabled')->default(false);
            $table->string('turnstile_site_key', 128)->nullable();
            $table->string('turnstile_secret_key', 128)->nullable();

            // ──────────────────────────────────────
            // RATE LIMITING (vorbereitet, default off)
            // ──────────────────────────────────────
            $table->boolean('rate_limit_enabled')->default(false);

            // Anonym
            $table->unsignedSmallInteger('anon_max_per_hour')->default(2);
            $table->unsignedSmallInteger('anon_max_per_day')->default(6);

            // Eingeloggt
            $table->unsignedSmallInteger('user_max_per_hour')->default(3);
            $table->unsignedSmallInteger('user_max_per_day')->default(10);

            // Cooldown zwischen Sessions (gleiche IP)
            $table->unsignedSmallInteger('cooldown_minutes')->default(5);

            // ──────────────────────────────────────
            // DEFAULT SESSION DURATION
            // ──────────────────────────────────────
            $table->unsignedSmallInteger('default_session_minutes')->default(20);
            // Pro Server überschreibbar in testservers.max_session_minutes

            // ──────────────────────────────────────
            // TEXTS (für Public-Page)
            // ──────────────────────────────────────
            $table->text('public_intro_text')->nullable();
            // "Teste hier Maps live auf unseren Testservern..."

            $table->text('public_rules_text')->nullable();
            // Regeln/Hinweise die User vor dem Reservieren sehen

            $table->timestamps();
        });

        // Seed: genau eine Row für globale Settings
        \DB::table('testserver_settings')->insert([
            'id' => 1,
            'feature_enabled'         => true,
            'public_visible'          => true,
            'require_login'           => false,
            'turnstile_enabled'       => false,
            'rate_limit_enabled'      => false,
            'anon_max_per_hour'       => 2,
            'anon_max_per_day'        => 6,
            'user_max_per_hour'       => 3,
            'user_max_per_day'        => 10,
            'cooldown_minutes'        => 5,
            'default_session_minutes' => 20,
            'public_intro_text'       => 'Teste hier Maps und Mods live auf unseren Testservern. Wähle einen freien Server und starte sofort eine Session.',
            'public_rules_text'       => 'Bitte fair spielen. Sessions sind zeitlich begrenzt. Bei Missbrauch wird die IP gesperrt.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('testserver_settings');
    }
};
