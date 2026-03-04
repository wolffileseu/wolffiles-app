<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        DB::statement("ALTER TABLE tracker_servers MODIFY COLUMN status ENUM('active','inactive','removed','pending') NOT NULL DEFAULT 'active'");
    }
    public function down(): void {
        DB::statement("ALTER TABLE tracker_servers MODIFY COLUMN status ENUM('active','inactive','removed') NOT NULL DEFAULT 'active'");
    }
};
