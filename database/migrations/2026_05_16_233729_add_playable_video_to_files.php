<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->string('playable_path', 500)->nullable()->after('file_hash');
            $table->string('playable_mime', 100)->nullable()->after('playable_path');
            $table->unsignedBigInteger('playable_size')->nullable()->after('playable_mime');
            $table->unsignedInteger('playable_duration_seconds')->nullable()->after('playable_size');
            $table->string('playable_codec', 50)->nullable()->after('playable_duration_seconds');
            // pending | processing | ready | failed | skipped
            $table->string('playable_status', 20)->nullable()->after('playable_codec');
            $table->string('playable_error', 500)->nullable()->after('playable_status');
            $table->timestamp('playable_processed_at')->nullable()->after('playable_error');

            $table->index('playable_status');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['playable_status']);
            $table->dropColumn([
                'playable_path', 'playable_mime', 'playable_size',
                'playable_duration_seconds', 'playable_codec',
                'playable_status', 'playable_error', 'playable_processed_at',
            ]);
        });
    }
};
