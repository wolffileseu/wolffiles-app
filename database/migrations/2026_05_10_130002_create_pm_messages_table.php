<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('pm_conversations')
                ->cascadeOnDelete();
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->mediumText('body')->nullable()
                ->comment('NULL after retention purge');
            $table->enum('body_format', ['markdown', 'plain'])->default('markdown');
            $table->timestamp('body_purged_at')->nullable()
                ->comment('When retention deleted body content');
            $table->timestamp('edited_at')->nullable();
            $table->string('ip_address', 45)->nullable()
                ->comment('Real client IP via mod_remoteip; IPv6 capable');
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_id', 'created_at'], 'pm_sender_pattern_idx');
            $table->index('body_purged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_messages');
    }
};
