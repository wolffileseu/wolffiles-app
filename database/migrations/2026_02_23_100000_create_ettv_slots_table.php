<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ettv_slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('slot_number')->unique();
            $table->unsignedSmallInteger('port')->unique();
            $table->string('pterodactyl_uuid', 36);
            $table->enum('status', ['idle', 'starting', 'playing', 'relay', 'reserved', 'error'])->default('idle');
            $table->enum('mode', ['demo', 'relay', 'showcase'])->nullable();
            $table->foreignId('demo_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('demo_name', 128)->nullable();
            $table->string('map_name', 64)->nullable();
            $table->string('match_server_ip', 64)->nullable();
            $table->unsignedSmallInteger('match_server_port')->nullable();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedTinyInteger('spectator_count')->default(0);
            $table->string('hostname', 128)->nullable();
            $table->string('reservation_reason', 255)->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ettv_slots');
    }
};
