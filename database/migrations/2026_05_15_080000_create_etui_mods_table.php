<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etui_mods', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name', 128);
            $table->text('description')->nullable();
            // FQN of the ModProfile subclass, e.g. App\Etui\Profiles\EtMainProfile.
            // EtuiMod::getProfileAttribute() instantiates via `new $fqn()`.
            $table->string('parser_profile', 255);
            $table->string('homepage_url', 255)->nullable();
            $table->string('icon_path', 255)->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etui_mods');
    }
};
