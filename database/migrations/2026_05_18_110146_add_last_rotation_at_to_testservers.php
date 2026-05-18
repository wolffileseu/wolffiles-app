<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testservers', function (Blueprint $table) {
            $table->timestamp('last_rotation_at')->nullable()->after('current_idle_mod');
        });
    }

    public function down(): void
    {
        Schema::table('testservers', function (Blueprint $table) {
            $table->dropColumn('last_rotation_at');
        });
    }
};
