<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testserver_sessions', function (Blueprint $table) {
            $table->id();

            // Welcher Server
            $table->foreignId('testserver_id')
                ->constrained('testservers')
                ->cascadeOnDelete();

            // Wer hat gestartet (anonym oder eingeloggt)
            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('session_token', 36)->unique();
            $table->string('ip_address', 45)->index();
            $table->string('user_agent', 512)->nullable();
            $table->string('country_code', 2)->nullable();

            // Was wird getestet
            $table->string('mod_name', 32);
            $table->string('map_slug', 128);
            $table->string('map_pk3_filename', 128)->nullable();
            $table->foreignId('map_file_id')->nullable()
                ->constrained('files')
                ->nullOnDelete();

            // Connect-Info
            $table->string('connect_address', 128);
            $table->string('connect_password', 16);

            // Lifecycle
            $table->enum('status', [
                'pending',
                'launching',
                'active',
                'expiring',
                'expired',
                'cancelled',
                'failed',
            ])->default('pending');
            $table->text('error_message')->nullable();

            // Timing — alle nullable, werden im Code gesetzt
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Stats
            $table->unsignedTinyInteger('peak_players')->default(0);
            $table->unsignedInteger('total_player_minutes')->default(0);
            $table->unsignedSmallInteger('snapshot_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['status', 'expires_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['map_slug', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testserver_sessions');
    }
};
