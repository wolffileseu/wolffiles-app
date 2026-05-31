<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('tracker_clans', function (Blueprint $t) {
            if (!Schema::hasColumn('tracker_clans','is_locked')) $t->boolean('is_locked')->default(false)->after('is_verified');
        });
    }
    public function down(): void {
        Schema::table('tracker_clans', function (Blueprint $t) {
            if (Schema::hasColumn('tracker_clans','is_locked')) $t->dropColumn('is_locked');
        });
    }
};
