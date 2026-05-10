<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_evidence_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('pm_conversations')
                ->cascadeOnDelete();
            $table->longText('snapshot_data')
                ->comment('Full JSON: conversation + participants + messages + attachments meta');
            $table->string('snapshot_hash', 64)
                ->comment('SHA256 of snapshot_data for integrity verification');
            $table->text('reason')
                ->comment('Mandatory: why was snapshot created');
            $table->foreignId('related_report_id')->nullable()
                ->constrained('pm_message_reports')
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Admin who created snapshot');
            $table->timestamp('created_at')->useCurrent();
            // No updated_at: write-once

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_evidence_snapshots');
    }
};
