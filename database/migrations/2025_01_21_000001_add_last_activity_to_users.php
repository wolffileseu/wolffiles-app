<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'last_activity_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_activity_at')->nullable()->after('last_login_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'last_activity_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_activity_at');
            });
        }
    }
};
