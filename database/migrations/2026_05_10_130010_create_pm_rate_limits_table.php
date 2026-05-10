<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('action', 50)
                ->comment('new_conversation, send_message');
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('window_start');
            $table->timestamps();

            $table->unique(['user_id', 'action', 'window_start'], 'pm_rate_limit_window_idx');
            $table->index('window_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_rate_limits');
    }
};
