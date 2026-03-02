<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // z.B. "Discord #announcements", "Reddit r/enemyterritory"
            $table->string('provider');                       // discord, reddit, twitter, facebook, custom
            $table->json('config')->nullable();               // API keys, tokens, webhook URLs etc.
            $table->json('enabled_events')->nullable();       // ['file_approved', 'donation', 'map_of_week']
            $table->boolean('is_active')->default(true);
            $table->text('message_template_file')->nullable();     // Custom template für File posts
            $table->text('message_template_donation')->nullable(); // Custom template für Donations
            $table->text('message_template_motw')->nullable();     // Custom template für Map of the Week
            $table->timestamp('last_posted_at')->nullable();
            $table->text('last_error')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_channels');
    }
};
