<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_bans', function (Blueprint $table) {
            // Stable identity anchor: real GUID at time of ban (survives merges/renames).
            $table->string('guid_snapshot', 64)->nullable()->after('guid_hash');
            $table->enum('type', ['cheat', 'ban', 'watch', 'cleared'])->default('ban')->after('source');
            $table->enum('status', ['pending', 'active', 'lifted', 'appealed'])->default('active')->after('type');
            // Public-facing reason (the internal `reason` stays admin-only).
            $table->string('public_reason', 255)->nullable()->after('reason');
            $table->boolean('is_public')->default(false)->after('public_reason');
            $table->foreignId('source_report_id')->nullable()->after('banned_by')
                  ->constrained('reports')->nullOnDelete();
            $table->timestamp('occurred_at')->nullable()->after('expires_at');

            $table->index('guid_snapshot');
            $table->index(['is_public', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tracker_bans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_report_id');
            $table->dropIndex(['guid_snapshot']);
            $table->dropIndex(['is_public', 'status']);
            $table->dropColumn(['guid_snapshot', 'type', 'status', 'public_reason', 'is_public', 'occurred_at']);
        });
    }
};
