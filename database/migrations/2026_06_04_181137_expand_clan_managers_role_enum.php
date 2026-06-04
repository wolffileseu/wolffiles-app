<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE clan_managers MODIFY COLUMN role ENUM('leader','owner','admin','editor') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE clan_managers MODIFY COLUMN role ENUM('owner','admin','editor') NOT NULL");
    }
};
