<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Games (ET, RtCW, RtCW Coop, etc.)
        Schema::create('fastdl_games', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Enemy Territory
            $table->string('slug')->unique();          // et
            $table->string('base_directory');           // etmain
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_sync')->default(false); // Auto-sync maps from Wolffiles DB
            $table->unsignedBigInteger('wolffiles_game_id')->nullable(); // Link to games table
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Directories (etmain, jaymod, etpro, noquarter, etc.)
        Schema::create('fastdl_directories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id');
            $table->foreign('game_id')->references('id')->on('fastdl_games')->cascadeOnDelete();
            $table->string('name');                    // Jaymod
            $table->string('slug');                    // jaymod
            $table->boolean('is_base')->default(false); // true = etmain (auto-synced)
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'slug']);
        });

        // Files in directories
        Schema::create('fastdl_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('directory_id');
            $table->foreign('directory_id')->references('id')->on('fastdl_directories')->cascadeOnDelete();
            $table->string('filename');                // goldrush.pk3
            $table->string('s3_path');                 // fastdl/et/etmain/goldrush.pk3
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('checksum')->nullable();    // MD5 for verification
            $table->string('source')->default('manual'); // manual, auto_sync, clan_upload
            $table->unsignedBigInteger('wolffiles_file_id')->nullable(); // Link to files table
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->timestamps();

            $table->unique(['directory_id', 'filename']);
            $table->index('wolffiles_file_id');
        });

        // Clan Fast-Download spaces
        Schema::create('fastdl_clans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // My Clan
            $table->string('slug')->unique();          // myclan
            $table->unsignedBigInteger('game_id');
            $table->foreign('game_id')->references('id')->on('fastdl_games');
            $table->unsignedBigInteger('leader_user_id')->nullable();
            $table->foreign('leader_user_id')->references('id')->on('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('include_base')->default(true); // Auto-include etmain
            $table->unsignedBigInteger('storage_limit_mb')->default(500); // Max upload space
            $table->unsignedBigInteger('storage_used_mb')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Which public directories a clan has selected
        Schema::create('fastdl_clan_directories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clan_id');
            $table->foreign('clan_id')->references('id')->on('fastdl_clans')->cascadeOnDelete();
            $table->unsignedBigInteger('directory_id');
            $table->foreign('directory_id')->references('id')->on('fastdl_directories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['clan_id', 'directory_id']);
        });

        // Clan's own uploaded files
        Schema::create('fastdl_clan_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clan_id');
            $table->foreign('clan_id')->references('id')->on('fastdl_clans')->cascadeOnDelete();
            $table->string('directory');                // etmain, jaymod, etc.
            $table->string('filename');
            $table->string('s3_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['clan_id', 'directory', 'filename']);
        });

        // Download log for stats
        Schema::create('fastdl_downloads', function (Blueprint $table) {
            $table->id();
            $table->string('path');                    // et/etmain/goldrush.pk3
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->unsignedBigInteger('clan_id')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fastdl_downloads');
        Schema::dropIfExists('fastdl_clan_files');
        Schema::dropIfExists('fastdl_clan_directories');
        Schema::dropIfExists('fastdl_clans');
        Schema::dropIfExists('fastdl_files');
        Schema::dropIfExists('fastdl_directories');
        Schema::dropIfExists('fastdl_games');
    }
};
