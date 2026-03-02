<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule', 128)->nullable();
            $table->string('team_axis', 64)->nullable();
            $table->string('team_allies', 64)->nullable();
            $table->string('map_name', 64)->nullable();
            $table->string('match_type', 32)->nullable();
            $table->string('gametype', 32)->default('stopwatch');
            $table->string('mod_name', 32)->default('etpro');
            $table->string('match_server_ip', 64)->nullable();
            $table->unsignedSmallInteger('match_server_port')->nullable();
            $table->boolean('ettv_enabled')->default(true);
            $table->unsignedTinyInteger('ettv_slot')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'live', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->unsignedTinyInteger('score_axis')->nullable();
            $table->unsignedTinyInteger('score_allies')->nullable();
            $table->foreignId('demo_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('image_url', 512)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->json('title_translations')->nullable();
            $table->json('description_translations')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('starts_at');
            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
