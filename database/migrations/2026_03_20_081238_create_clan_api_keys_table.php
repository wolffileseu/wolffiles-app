<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("clan_api_keys", function (Blueprint $table) {
            $table->id();
            $table->foreignId("clan_id")->constrained()->cascadeOnDelete();
            $table->string("key", 64)->unique();
            $table->string("label")->nullable();
            $table->boolean("is_active")->default(true);
            $table->timestamp("last_used_at")->nullable();
            $table->timestamp("expires_at")->nullable();
            $table->timestamps();
            $table->index(["key", "is_active"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("clan_api_keys");
    }
};
