<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tracker_players', function (Blueprint $table) {
            $table->string('avatar_url', 500)->nullable()->after('bio');
            $table->string('banner_url', 500)->nullable()->after('avatar_url');
            $table->string('youtube_url', 255)->nullable()->after('banner_url');
            $table->string('twitch_url', 255)->nullable()->after('youtube_url');
            $table->string('discord_url', 255)->nullable()->after('twitch_url');
            $table->string('twitter_url', 255)->nullable()->after('discord_url');
            $table->string('website_url', 255)->nullable()->after('twitter_url');
        });
    }

    public function down(): void
    {
        Schema::table('tracker_players', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_url', 'banner_url',
                'youtube_url', 'twitch_url', 'discord_url', 'twitter_url', 'website_url',
            ]);
        });
    }
};
