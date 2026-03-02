<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tracker_servers', 'claimed_by_user_id')) {
            Schema::table('tracker_servers', function (Blueprint $table) {
                $table->foreignId('claimed_by_user_id')->nullable()->after('status');
                $table->boolean('is_verified')->default(false)->after('claimed_by_user_id');
                $table->text('description')->nullable()->after('is_verified');
                $table->string('server_website')->nullable()->after('description');
                $table->string('server_discord')->nullable()->after('server_website');
                $table->string('server_email')->nullable()->after('server_discord');
                $table->string('server_banner_url')->nullable()->after('server_email');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropColumn([
                'claimed_by_user_id', 'is_verified', 'description',
                'server_website', 'server_discord', 'server_email', 'server_banner_url',
            ]);
        });
    }
};
