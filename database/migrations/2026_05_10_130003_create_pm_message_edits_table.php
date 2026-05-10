<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_message_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')
                ->constrained('pm_messages')
                ->cascadeOnDelete();
            $table->mediumText('old_body');
            $table->timestamp('edited_at');
            $table->string('edited_from_ip', 45)->nullable();
            // No updated_at: write-once
            $table->timestamp('created_at')->useCurrent();

            $table->index(['message_id', 'edited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_message_edits');
    }
};
