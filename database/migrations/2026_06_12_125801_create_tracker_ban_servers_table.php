<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_ban_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ban_id')->constrained('tracker_bans')->cascadeOnDelete();
            $table->foreignId('server_id')->constrained('tracker_servers')->cascadeOnDelete();
            $table->unique(['ban_id', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_ban_servers');
    }
};
