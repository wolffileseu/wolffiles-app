<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fastdl_clan_admins', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clan_id')->constrained('fastdl_clans')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['clan_id', 'user_id']);
        });
        DB::table('fastdl_clans')->whereNotNull('leader_user_id')->orderBy('id')
            ->chunk(200, function ($clans) {
                $rows = [];
                foreach ($clans as $c) {
                    $rows[] = ['clan_id' => $c->id, 'user_id' => $c->leader_user_id,
                        'created_at' => now(), 'updated_at' => now()];
                }
                if ($rows) { DB::table('fastdl_clan_admins')->insertOrIgnore($rows); }
            });
    }
    public function down(): void
    {
        Schema::dropIfExists('fastdl_clan_admins');
    }
};
