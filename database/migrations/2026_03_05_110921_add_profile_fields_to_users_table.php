<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_username')->nullable()->after('discord_username');
            $table->string('clan')->nullable()->after('telegram_username');
            $table->json('favorite_games')->nullable()->after('clan');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_username', 'clan', 'favorite_games']);
        });
    }
};
