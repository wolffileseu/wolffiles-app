<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_player_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // reporter (logged in)
            $table->foreignId('reported_player_id')->nullable()->constrained('tracker_players')->nullOnDelete();
            $table->string('reported_guid', 64)->nullable();   // optionally provided by reporter
            $table->text('description');                        // what they witnessed
            $table->string('contact', 255)->nullable();         // Discord etc. for follow-up
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('resulting_ban_id')->nullable()->constrained('tracker_bans')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('reported_player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_player_reports');
    }
};
