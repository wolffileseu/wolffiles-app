<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_players', function (Blueprint $table) {
            // When set, this player was merged INTO the referenced player id.
            // The row is kept (not deleted) so merges stay reversible.
            $table->unsignedBigInteger('merged_into')->nullable()->after('status');
            $table->timestamp('merged_at')->nullable()->after('merged_into');
            $table->index('merged_into');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_players', function (Blueprint $table) {
            $table->dropIndex(['merged_into']);
            $table->dropColumn(['merged_into', 'merged_at']);
        });
    }
};
