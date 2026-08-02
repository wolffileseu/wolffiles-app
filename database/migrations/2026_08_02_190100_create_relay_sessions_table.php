<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relay_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('relay_node_id')
                  ->constrained('relay_nodes')
                  ->cascadeOnUpdate()
                  ->cascadeOnDelete();

            // Nullable so history survives if a tracked server gets removed
            $table->unsignedBigInteger('tracker_server_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->enum('game', ['et', 'rtcw'])->default('et');

            // Snapshot of the target, independent of tracker_servers lifetime
            $table->string('target_ip', 45);
            $table->integer('target_port');

            // Source address the agent bound out of the IPv6 prefix
            $table->string('source_addr', 45)->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->string('client_country', 2)->nullable();

            $table->char('ticket_id', 36)->unique();
            $table->timestamp('ticket_expires_at')->nullable();

            $table->unsignedBigInteger('bytes_in')->default(0);
            $table->unsignedBigInteger('bytes_out')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('ended_reason', 32)->nullable();

            $table->timestamps();

            $table->foreign('tracker_server_id')
                  ->references('id')->on('tracker_servers')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnUpdate()
                  ->nullOnDelete();

            $table->index(['relay_node_id', 'ended_at'], 'relay_sessions_node_ended_index');
            $table->index(['tracker_server_id', 'started_at'], 'relay_sessions_server_started_index');
            $table->index('started_at', 'relay_sessions_started_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relay_sessions');
    }
};
