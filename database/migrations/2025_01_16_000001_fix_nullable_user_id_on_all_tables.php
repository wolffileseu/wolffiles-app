<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pages
        if (Schema::hasColumn('pages', 'user_id')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->change();
            });
        }

        // Posts
        if (Schema::hasColumn('posts', 'user_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->change();
            });
        }

        // Lua scripts
        if (Schema::hasColumn('lua_scripts', 'user_id')) {
            Schema::table('lua_scripts', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->change();
            });
        }
    }

    public function down(): void {}
};
