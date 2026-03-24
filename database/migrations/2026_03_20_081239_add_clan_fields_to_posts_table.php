<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("posts", function (Blueprint $table) {
            $table->string("type")->default("news")->after("clan_id");
            $table->timestamp("event_date")->nullable()->after("type");
            $table->string("event_location")->nullable()->after("event_date");
            $table->string("match_opponent")->nullable()->after("event_location");
            $table->string("match_result")->nullable()->after("match_opponent");
            $table->string("match_map")->nullable()->after("match_result");
            $table->json("recruitment_requirements")->nullable()->after("match_map");
            $table->index(["type", "is_published"]);
            $table->index("clan_id");
        });
    }

    public function down(): void
    {
        Schema::table("posts", function (Blueprint $table) {
            $table->dropForeign(["clan_id"]);
            $table->dropIndex(["type", "is_published"]);
            $table->dropIndex(["clan_id"]);
            $table->dropColumn(["clan_id", "type", "event_date", "event_location",
                "match_opponent", "match_result", "match_map", "recruitment_requirements"]);
        });
    }
};
