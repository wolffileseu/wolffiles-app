<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('files', 'map_name_clean')) {
            return;
        }

        DB::statement("
            ALTER TABLE files
            ADD COLUMN map_name_clean VARCHAR(255) AS (
                LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                    map_name, '^0',''), '^1',''), '^2',''), '^3',''), '^4',''), '^5',''), '^6',''), '^7',''), '^8',''), '^9',''))
            ) PERSISTENT
        ");

        DB::statement('CREATE INDEX idx_files_map_name_clean ON files(map_name_clean)');
        DB::statement('CREATE INDEX idx_files_status_map_clean ON files(status, map_name_clean)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_files_status_map_clean ON files');
        DB::statement('DROP INDEX IF EXISTS idx_files_map_name_clean ON files');

        if (Schema::hasColumn('files', 'map_name_clean')) {
            Schema::table('files', function ($table) {
                $table->dropColumn('map_name_clean');
            });
        }
    }
};
