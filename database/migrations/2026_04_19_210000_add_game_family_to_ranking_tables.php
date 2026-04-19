<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_server_rankings', function (Blueprint $table) {
            $table->string('game_family', 10)->nullable()->after('game_id');
            $table->index(['game_family', 'rank'], 'tsr_family_rank_idx');
        });

        Schema::table('tracker_player_rankings_30d', function (Blueprint $table) {
            $table->string('game_family', 10)->nullable()->after('game_id');
            $table->dropUnique('tracker_player_rankings_30d_player_id_game_id_unique');
            $table->unique(['player_id', 'game_family'], 'tpr30d_player_family_unique');
            $table->index(['game_family', 'rank'], 'tpr30d_family_rank_idx');
            $table->index(['game_family', 'playtime_minutes_30d'], 'tpr30d_family_playtime_idx');
            $table->index(['game_family', 'elo_rating'], 'tpr30d_family_elo_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_server_rankings', function (Blueprint $table) {
            $table->dropIndex('tsr_family_rank_idx');
            $table->dropColumn('game_family');
        });

        Schema::table('tracker_player_rankings_30d', function (Blueprint $table) {
            $table->dropIndex('tpr30d_family_rank_idx');
            $table->dropIndex('tpr30d_family_playtime_idx');
            $table->dropIndex('tpr30d_family_elo_idx');
            $table->dropUnique('tpr30d_player_family_unique');
            $table->unique(['player_id', 'game_id'], 'tracker_player_rankings_30d_player_id_game_id_unique');
            $table->dropColumn('game_family');
        });
    }
};
