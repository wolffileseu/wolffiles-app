<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('clan_applications')) return;
        Schema::create('clan_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('clan_id')->constrained('clans')->cascadeOnDelete();
            $t->foreignId('applicant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('player_name');
            $t->string('contact')->nullable();
            $t->text('message');
            $t->enum('status', ['pending','accepted','rejected','withdrawn'])->default('pending');
            $t->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->index(['clan_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('clan_applications'); }
};
