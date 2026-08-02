<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relay_nodes', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);
            $table->string('hostname', 255)->nullable();
            $table->string('region', 32)->default('eu');

            // Public WebSocket endpoint the browser client connects to
            $table->string('ws_url', 255);

            // Routed IPv6 prefix used for per-session source addresses
            $table->string('ipv6_prefix', 64)->nullable();
            $table->string('ipv4_pool', 255)->nullable();

            // Shared secret for HMAC ticket validation + heartbeat auth
            $table->string('agent_secret', 128);

            $table->unsignedSmallInteger('max_sessions')->default(200);
            $table->unsignedSmallInteger('active_sessions')->default(0);

            $table->boolean('enabled')->default(true);
            $table->enum('status', ['online', 'offline', 'degraded', 'disabled'])
                  ->default('offline');

            $table->decimal('load_avg', 5, 2)->nullable();
            $table->unsignedSmallInteger('agent_rtt_ms')->nullable();
            $table->string('agent_version', 32)->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['enabled', 'status'], 'relay_nodes_enabled_status_index');
            $table->index('region', 'relay_nodes_region_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relay_nodes');
    }
};
