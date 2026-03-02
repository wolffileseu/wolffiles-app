<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Falls die badges Tabelle noch keine 'slug' Spalte hat
        // (auskommentieren wenn bereits vorhanden)
        if (! Schema::hasColumn('badges', 'slug')) {
            Schema::table('badges', function (Blueprint $table) {
                $table->string('slug')->unique()->after('name');
            });
        }

        if (! Schema::hasColumn('badges', 'icon')) {
            Schema::table('badges', function (Blueprint $table) {
                $table->string('icon')->nullable()->after('slug');
            });
        }

        // Falls die Pivot-Tabelle noch kein 'awarded_at' hat
        if (! Schema::hasColumn('user_badges', 'awarded_at')) {
            Schema::table('user_badges', function (Blueprint $table) {
                $table->timestamp('awarded_at')->nullable()->after('badge_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('badges', 'slug')) {
            Schema::table('badges', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }

        if (Schema::hasColumn('badges', 'icon')) {
            Schema::table('badges', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }

        if (Schema::hasColumn('user_badges', 'awarded_at')) {
            Schema::table('user_badges', function (Blueprint $table) {
                $table->dropColumn('awarded_at');
            });
        }
    }
};
