<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('tracker_servers', function (Blueprint $t) {
            if (!Schema::hasColumn('tracker_servers','claimed_by_clan_id')) $t->foreignId('claimed_by_clan_id')->nullable()->after('claimed_by_user_id')->constrained('clans')->nullOnDelete();
            if (!Schema::hasColumn('tracker_servers','rules')) $t->text('rules')->nullable()->after('description');
            if (!Schema::hasColumn('tracker_servers','is_locked')) $t->boolean('is_locked')->default(false)->after('is_verified');
        });
    }
    public function down(): void {
        Schema::table('tracker_servers', function (Blueprint $t) {
            if (Schema::hasColumn('tracker_servers','claimed_by_clan_id')) $t->dropConstrainedForeignId('claimed_by_clan_id');
            foreach (['rules','is_locked'] as $c) if (Schema::hasColumn('tracker_servers',$c)) $t->dropColumn($c);
        });
    }
};
