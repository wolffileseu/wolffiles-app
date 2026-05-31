<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clan_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tag', 50);
            $table->string('tag_clean', 50);
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('discord')->nullable();
            $table->enum('status', ['pending','approved','rejected','merged'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('created_tracker_clan_id')->nullable()->constrained('tracker_clans')->nullOnDelete();
            $table->timestamps();
            $table->index(['status']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clan_proposals');
    }
};
