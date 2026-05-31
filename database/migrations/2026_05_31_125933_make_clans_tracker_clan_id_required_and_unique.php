<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Drop existing FK constraint (added by extend_clans_for_pages)
        Schema::table('clans', function (Blueprint $table) {
            $table->dropForeign(['tracker_clan_id']);
        });

        // 2) Change column to NOT NULL
        Schema::table('clans', function (Blueprint $table) {
            $table->unsignedBigInteger('tracker_clan_id')->nullable(false)->change();
        });

        // 3) Add UNIQUE index (one tracker_clan = one registered clan)
        Schema::table('clans', function (Blueprint $table) {
            $table->unique('tracker_clan_id', 'clans_tracker_clan_id_unique');
        });

        // 4) Re-create FK constraint
        Schema::table('clans', function (Blueprint $table) {
            $table->foreign('tracker_clan_id')
                  ->references('id')->on('tracker_clans')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clans', function (Blueprint $table) {
            $table->dropForeign(['tracker_clan_id']);
        });
        Schema::table('clans', function (Blueprint $table) {
            $table->dropUnique('clans_tracker_clan_id_unique');
        });
        Schema::table('clans', function (Blueprint $table) {
            $table->unsignedBigInteger('tracker_clan_id')->nullable()->change();
        });
        Schema::table('clans', function (Blueprint $table) {
            $table->foreign('tracker_clan_id')
                  ->references('id')->on('tracker_clans')
                  ->nullOnDelete();
        });
    }
};
