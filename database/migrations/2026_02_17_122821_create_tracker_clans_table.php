<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_clans', function (Blueprint $table) {
            $table->id();
            $table->string('tag', 50);
            $table->string('tag_clean', 50);
            $table->string('name')->nullable();
            $table->string('website')->nullable();
            $table->string('country', 100)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->integer('member_count')->default(0);
            $table->decimal('avg_elo', 8, 2)->default(1000.00);
            $table->integer('total_play_time_minutes')->default(0);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->enum('status', ['active', 'inactive', 'merged'])->default('active');
            $table->timestamps();
            $table->index('tag_clean');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_clans');
    }
};
