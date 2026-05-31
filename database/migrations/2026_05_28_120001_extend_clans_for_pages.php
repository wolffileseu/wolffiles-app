<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('clans', function (Blueprint $t) {
            if (!Schema::hasColumn('clans','tracker_clan_id')) $t->foreignId('tracker_clan_id')->nullable()->after('id')->constrained('tracker_clans')->nullOnDelete();
            if (!Schema::hasColumn('clans','rules')) $t->text('rules')->nullable()->after('description');
            if (!Schema::hasColumn('clans','location')) $t->string('location')->nullable()->after('rules');
            if (!Schema::hasColumn('clans','founded')) $t->string('founded')->nullable()->after('location');
            if (!Schema::hasColumn('clans','banner')) $t->string('banner')->nullable()->after('logo');
            if (!Schema::hasColumn('clans','ts_address')) $t->string('ts_address')->nullable()->after('contact_email');
            if (!Schema::hasColumn('clans','is_published')) $t->boolean('is_published')->default(true)->after('is_active');
            if (!Schema::hasColumn('clans','is_recruiting')) $t->boolean('is_recruiting')->default(false)->after('is_published');
            if (!Schema::hasColumn('clans','recruitment_summary')) $t->text('recruitment_summary')->nullable()->after('is_recruiting');
            if (!Schema::hasColumn('clans','view_count')) $t->unsignedBigInteger('view_count')->default(0)->after('recruitment_summary');
        });
    }
    public function down(): void {
        Schema::table('clans', function (Blueprint $t) {
            if (Schema::hasColumn('clans','tracker_clan_id')) $t->dropConstrainedForeignId('tracker_clan_id');
            foreach (['rules','location','founded','banner','ts_address','is_published','is_recruiting','recruitment_summary','view_count'] as $c)
                if (Schema::hasColumn('clans',$c)) $t->dropColumn($c);
        });
    }
};
