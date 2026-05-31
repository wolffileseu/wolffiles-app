<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->boolean('is_visible_for_clan')->default(false)->after('claimed_by_clan_id');
            $table->index('is_visible_for_clan');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropIndex(['is_visible_for_clan']);
            $table->dropColumn('is_visible_for_clan');
        });
    }
};
