<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->nullable()->constrained('tracker_players')->nullOnDelete();
            $table->string('guid_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('reason')->nullable();
            $table->enum('source', ['manual', 'anticheat', 'vote', 'imported'])->default('manual');
            $table->foreignId('banned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('guid_hash');
            $table->index('ip_address');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_bans');
    }
};
