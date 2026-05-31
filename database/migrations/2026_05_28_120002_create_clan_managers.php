<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('clan_managers')) return;
        Schema::create('clan_managers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clan_id')->constrained('clans')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->enum('role', ['owner','admin','editor'])->default('editor');
            $t->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['clan_id','user_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('clan_managers'); }
};
