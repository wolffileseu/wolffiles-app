<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('claimable_type');        // 'player' or 'clan'
            $table->unsignedBigInteger('claimable_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Evidence / proof
            $table->text('message')->nullable();       // "I am this player because..."
            $table->string('proof_type')->nullable();  // screenshot, guid, server_admin, etc.

            // Clan-specific extra info (filled during claim)
            $table->string('clan_email')->nullable();
            $table->string('clan_website')->nullable();
            $table->string('clan_discord')->nullable();
            $table->text('clan_description')->nullable();

            // Moderation
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['claimable_type', 'claimable_id']);
            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        // Ensure tracker_players has user_id column
        if (!Schema::hasColumn('tracker_players', 'claimed_by_user_id')) {
            Schema::table('tracker_players', function (Blueprint $table) {
                $table->foreignId('claimed_by_user_id')->nullable()->after('user_id');
                $table->boolean('is_verified')->default(false)->after('claimed_by_user_id');
            });
        }

        // Ensure tracker_clans has all claim-related columns
        if (!Schema::hasColumn('tracker_clans', 'clan_email')) {
            Schema::table('tracker_clans', function (Blueprint $table) {
                $table->string('clan_email')->nullable()->after('discord');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_claims');
    }
};
