<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pm_user_settings', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->primary()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('who_can_message', ['everyone', 'clan_only', 'nobody'])
                ->default('everyone');
            $table->boolean('email_notify')->default(true);
            $table->boolean('discord_notify')->default(false);
            $table->boolean('telegram_notify')->default(false);
            $table->unsignedSmallInteger('notification_throttle_minutes')->default(15)
                ->comment('Coalescing window for notifications');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_user_settings');
    }
};
