<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracker_server_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('tracker_servers')->cascadeOnDelete();
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['server_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracker_server_settings');
    }
};
