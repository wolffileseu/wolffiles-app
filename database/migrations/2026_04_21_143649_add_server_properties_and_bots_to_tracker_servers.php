<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            // Bot count from omnibot_playing setting (included in current_players total)
            $table->unsignedSmallInteger('bot_count')->nullable()->after('private_slots');

            // Server property flags parsed from getstatus settings.
            // Displayed as icons in the server list and detail page.
            $table->boolean('friendly_fire')->nullable()->after('needs_password');
            $table->boolean('antilag')->nullable()->after('friendly_fire');
            $table->boolean('balanced_teams')->nullable()->after('antilag');
            $table->unsignedTinyInteger('heavy_weapon_restriction')->nullable()->after('balanced_teams');
            $table->string('anticheat', 32)->nullable()->after('heavy_weapon_restriction');

            // g_oss bitfield: 1=Windows, 2=Mac, 4=Linux (can be combined)
            $table->unsignedTinyInteger('os_support')->nullable()->after('os');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_servers', function (Blueprint $table) {
            $table->dropColumn([
                'bot_count',
                'friendly_fire', 'antilag', 'balanced_teams',
                'heavy_weapon_restriction', 'anticheat', 'os_support'
            ]);
        });
    }
};
