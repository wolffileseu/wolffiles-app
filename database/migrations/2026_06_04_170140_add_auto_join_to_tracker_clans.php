<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_clans', function (Blueprint $table) {
            $table->boolean('auto_join_enabled')->default(false)->after('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_clans', function (Blueprint $table) {
            $table->dropColumn('auto_join_enabled');
        });
    }
};
