<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('tracker_clan_members', function (Blueprint $t) {
            if (!Schema::hasColumn('tracker_clan_members','role_label')) $t->string('role_label')->nullable()->after('role');
            if (!Schema::hasColumn('tracker_clan_members','squad_id')) $t->foreignId('squad_id')->nullable()->after('role_label')->constrained('tracker_clan_squads')->nullOnDelete();
            if (!Schema::hasColumn('tracker_clan_members','is_manual')) $t->boolean('is_manual')->default(false)->after('squad_id');
            if (!Schema::hasColumn('tracker_clan_members','sort_order')) $t->integer('sort_order')->default(0)->after('is_manual');
        });
    }
    public function down(): void {
        Schema::table('tracker_clan_members', function (Blueprint $t) {
            if (Schema::hasColumn('tracker_clan_members','squad_id')) $t->dropConstrainedForeignId('squad_id');
            foreach (['role_label','is_manual','sort_order'] as $c) if (Schema::hasColumn('tracker_clan_members',$c)) $t->dropColumn($c);
        });
    }
};
