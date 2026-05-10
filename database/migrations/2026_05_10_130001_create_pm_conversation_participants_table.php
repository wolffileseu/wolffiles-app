<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                ->constrained('pm_conversations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable()
                ->comment('User left group conversation');
            $table->timestamp('last_read_at')->nullable()
                ->comment('For unread counter');
            $table->timestamp('deleted_at')->nullable()
                ->comment('Per-user soft delete from inbox');
            $table->boolean('muted')->default(false);
            $table->boolean('is_creator')->default(false)
                ->comment('Can add new participants, lock conversation');
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'deleted_at', 'last_read_at'], 'pm_inbox_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_conversation_participants');
    }
};
