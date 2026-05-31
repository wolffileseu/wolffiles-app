<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clans', function (Blueprint $table) {
            $table->dropUnique('clans_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::table('clans', function (Blueprint $table) {
            $table->unique('tag', 'clans_tag_unique');
        });
    }
};
