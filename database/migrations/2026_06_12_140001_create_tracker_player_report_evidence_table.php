<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_player_report_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('tracker_player_reports')->cascadeOnDelete();
            $table->string('file_path', 512);   // S3 path (screenshot)
            $table->string('caption', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_player_report_evidence');
    }
};
