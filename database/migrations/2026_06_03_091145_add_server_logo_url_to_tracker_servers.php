<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->string('server_logo_url', 500)->nullable()->after('server_banner_url');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropColumn('server_logo_url');
        });
    }
};
