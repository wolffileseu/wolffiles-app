<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testservers', function (Blueprint $table) {
            $table->string('current_idle_mod', 32)->nullable()->after('default_mod');
        });
    }

    public function down(): void
    {
        Schema::table('testservers', function (Blueprint $table) {
            $table->dropColumn('current_idle_mod');
        });
    }
};
