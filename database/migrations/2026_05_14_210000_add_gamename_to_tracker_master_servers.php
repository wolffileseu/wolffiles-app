<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_master_servers', function (Blueprint $t) {
            $t->string('gamename', 32)->nullable()->after('port');
            $t->unsignedSmallInteger('protocol_override')->nullable()->after('gamename');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_master_servers', function (Blueprint $t) {
            $t->dropColumn(['gamename', 'protocol_override']);
        });
    }
};
