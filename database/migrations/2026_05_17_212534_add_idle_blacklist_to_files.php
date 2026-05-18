<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->boolean('idle_pool_blacklisted')->default(false)->after('status');
            $table->string('idle_pool_blacklist_reason', 255)->nullable()->after('idle_pool_blacklisted');
            $table->index('idle_pool_blacklisted');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['idle_pool_blacklisted']);
            $table->dropColumn(['idle_pool_blacklisted', 'idle_pool_blacklist_reason']);
        });
    }
};
