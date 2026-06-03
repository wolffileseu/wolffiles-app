<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->string('slug', 50)->nullable()->unique()->after('hostname_clean');
            $table->timestamp('slug_changed_at')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'slug_changed_at']);
        });
    }
};
