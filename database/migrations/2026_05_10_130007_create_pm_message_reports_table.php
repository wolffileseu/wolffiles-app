<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_message_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('message_id')
                ->constrained('pm_messages')
                ->cascadeOnDelete();
            $table->foreignId('conversation_id')
                ->constrained('pm_conversations')
                ->cascadeOnDelete()
                ->comment('Denormalized for fast admin filter');
            $table->string('reason_code', 50)
                ->comment('spam, harassment, illegal, threat, other');
            $table->text('reason_text')->nullable();
            $table->enum('status', ['open', 'reviewing', 'resolved', 'dismissed'])
                ->default('open');
            $table->foreignId('resolved_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_message_reports');
    }
};
