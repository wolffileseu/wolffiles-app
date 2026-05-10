<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 200)->nullable();
            $table->enum('type', ['direct', 'group'])->default('direct');
            $table->string('hash_key', 64)->nullable()->unique()
                ->comment('SHA256 of sorted user ids; only for type=direct, prevents duplicate 1:1 threads');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedInteger('message_count')->default(0);
            $table->boolean('locked')->default(false)
                ->comment('Locked by mod/admin, no new messages allowed');
            $table->timestamps();

            $table->index(['type', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_conversations');
    }
};
