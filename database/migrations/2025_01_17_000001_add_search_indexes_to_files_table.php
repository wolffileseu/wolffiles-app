<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add FULLTEXT index for search
        try {
            DB::statement('ALTER TABLE files ADD FULLTEXT INDEX files_fulltext_search (title, description, map_name, original_author)');
        } catch (\Exception $e) {
            // Index might already exist
        }

        // Also add regular indexes for LIKE fallback
        Schema::table('files', function (Blueprint $table) {
            $table->index('title');
            $table->index('map_name');
            $table->index('original_author');
        });
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE files DROP INDEX files_fulltext_search');
        } catch (\Exception $e) {}

        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropIndex(['map_name']);
            $table->dropIndex(['original_author']);
        });
    }
};
