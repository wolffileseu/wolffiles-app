<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tracker_player_match_stats', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('match_id')->constrained('tracker_matches')->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('tracker_servers');    // denormalized for faster "per-server stats"
            $table->foreignId('player_id')->constrained('tracker_players');

            // GUID hash — same hashing as tracker_players.guid_hash (SHA-256).
            // We never store raw ET GUIDs for GDPR reasons.
            $table->string('guid_hash', 64)->index();

            // Name as used in this specific match (may contain color codes)
            $table->string('name_snapshot', 64);
            $table->string('name_clean_snapshot', 64)->nullable();

            // Match-scoped identity
            $table->unsignedTinyInteger('slot')->nullable();              // 0-63
            $table->unsignedTinyInteger('team')->nullable()->index();     // 1=Axis, 2=Allies, 3=Spec
            $table->unsignedTinyInteger('class')->nullable();             // 0=Soldier, 1=Medic, 2=Engi, 3=FOps, 4=Covert

            // Primary metrics
            $table->unsignedSmallInteger('kills')->default(0);
            $table->unsignedSmallInteger('deaths')->default(0);
            $table->unsignedSmallInteger('headshots')->default(0);
            $table->unsignedSmallInteger('team_kills')->default(0);
            $table->unsignedSmallInteger('suicides')->default(0);
            $table->unsignedInteger('damage_given')->default(0);
            $table->unsignedInteger('damage_received')->default(0);
            $table->decimal('accuracy_pct', 5, 2)->nullable();            // e.g. 92.45

            // Meta
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('ping_avg')->nullable();
            $table->unsignedInteger('playtime_seconds')->default(0);

            // Class-specific — only populated when relevant class was played
            $table->unsignedSmallInteger('revives_given')->default(0);    // Medic
            $table->unsignedSmallInteger('revives_received')->default(0);
            $table->unsignedSmallInteger('objectives_taken')->default(0); // any class

            // Weapon breakdown as JSON. Shape:
            //   { "mp40":   {"shots": 150, "hits": 42, "kills": 8,  "deaths": 2, "headshots": 3},
            //     "knife":  {"shots": 5,   "hits": 5,  "kills": 5,  "deaths": 0, "headshots": 0},
            //     ...
            //   }
            $table->json('weapons_used')->nullable();

            // Raw bitmask for future re-parsing if we improve the decoder
            $table->unsignedInteger('weapon_bitmask')->default(0);

            $table->timestamps();

            // Indexes for common queries
            $table->unique(['match_id', 'slot'], 'unique_match_slot');
            $table->index(['player_id', 'created_at'], 'idx_player_time');
            $table->index(['server_id', 'created_at'], 'idx_server_time');
            $table->index(['guid_hash', 'created_at'], 'idx_guid_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_player_match_stats');
    }
};
