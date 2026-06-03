<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_players', function (Blueprint $table) {
            $table->boolean('country_locked')->default(false)->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_players', function (Blueprint $table) {
            $table->dropColumn('country_locked');
        });
    }
};
