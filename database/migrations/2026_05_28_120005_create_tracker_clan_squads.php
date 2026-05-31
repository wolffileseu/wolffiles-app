<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('tracker_clan_squads')) return;
        Schema::create('tracker_clan_squads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clan_id')->constrained('tracker_clans')->cascadeOnDelete();
            $t->string('name');
            $t->string('description')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tracker_clan_squads'); }
};
