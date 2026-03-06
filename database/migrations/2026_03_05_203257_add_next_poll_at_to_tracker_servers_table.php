<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->timestamp('next_poll_at')->nullable()->after('last_poll_at')->index();
        });
    }
    public function down(): void {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropColumn('next_poll_at');
        });
    }
};
