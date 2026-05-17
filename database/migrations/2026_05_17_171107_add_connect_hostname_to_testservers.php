<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testservers', function (Blueprint $table) {
            $table->string('connect_hostname', 100)->nullable()->after('connect_ip');
        });
    }

    public function down(): void
    {
        Schema::table('testservers', function (Blueprint $table) {
            $table->dropColumn('connect_hostname');
        });
    }
};
