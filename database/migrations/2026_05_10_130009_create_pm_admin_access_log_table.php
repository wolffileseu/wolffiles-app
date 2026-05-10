<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_admin_access_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()
                ->constrained('pm_conversations')
                ->nullOnDelete();
            $table->foreignId('message_id')->nullable()
                ->constrained('pm_messages')
                ->nullOnDelete();
            $table->enum('action', [
                'view_inbox',
                'view_conversation',
                'view_message',
                'create_snapshot',
                'export',
                'lock',
                'unlock',
                'resolve_report',
            ]);
            $table->string('reason', 500)
                ->comment('Mandatory for view_* actions');
            $table->string('admin_ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at: write-once

            $table->index(['admin_id', 'created_at']);
            $table->index(['conversation_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_admin_access_log');
    }
};
