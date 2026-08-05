<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sup_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // Wer darf hier rein? leer = jeder Staff mit support.view_all
            $table->string('required_permission', 120)->nullable();

            // Discord: Parent-Channel fuer private Threads + Rolle fuer Alerts
            $table->string('discord_channel_id', 32)->nullable();
            $table->string('discord_role_id', 32)->nullable();

            $table->foreignId('default_assignee_id')->nullable()
                ->constrained('users')->nullOnDelete();

            // Duerfen Gaeste (ohne Account) hier Tickets aufmachen?
            $table->boolean('allow_guests')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sup_categories');
    }
};
