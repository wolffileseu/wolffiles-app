<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Raw event storage — every UDP packet as received, decoded only at the envelope level.
 * Used for:
 *   - debugging "why didn't this parse correctly?"
 *   - re-processing historical data after parser improvements
 *   - forensics if a server is sending malformed/spoofed data
 *
 * Write-heavy table. No relations, no FKs — keep inserts as cheap as possible.
 * Partitioning/archiving can be added later; for now rely on received_at index
 * so admins can issue `DELETE WHERE received_at < X` when needed.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tracker_raw_events', function (Blueprint $table) {
            $table->id();
            $table->timestamp('received_at', 3);                     // millisecond precision
            $table->string('source_ip', 45);                         // IPv4 or IPv6
            $table->unsignedInteger('source_port');
            $table->string('cmd', 16);                               // start, stop, map, ws, wsc, p, name, connect, disconnect, maprestart, mapend, unknown
            $table->unsignedInteger('size_bytes');
            $table->text('payload');                                 // ASCII body stripped of OOB prefix
            // Processing state
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at', 3)->nullable();
            $table->string('processing_error', 255)->nullable();
            // Link to server after we figure out which server this came from.
            // Nullable because the very first packet from an unknown IP arrives before
            // we've created the tracker_servers row.
            $table->foreignId('server_id')->nullable()->constrained('tracker_servers')->nullOnDelete();

            // No created_at/updated_at — received_at is the truth, and this table is append-only.

            // Index strategy:
            //   - received_at for time-based queries ("last hour", "delete older than X")
            //   - (source_ip, received_at) for "show all packets from that server"
            //   - (cmd, received_at) for "show all ws packets recently"
            //   - processed for the job that picks up unprocessed events
            $table->index('received_at', 'idx_raw_received_at');
            $table->index(['source_ip', 'received_at'], 'idx_raw_src_time');
            $table->index(['cmd', 'received_at'], 'idx_raw_cmd_time');
            $table->index(['processed', 'received_at'], 'idx_raw_unprocessed');
            $table->index(['server_id', 'received_at'], 'idx_raw_server_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_raw_events');
    }
};
