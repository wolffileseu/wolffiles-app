<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etui_projects', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete on both FKs — Phase 3 will surface a proper
            // soft-delete or transfer policy if user accounts are removed
            // while sharing live share_token links. For Phase 2 we error
            // rather than silently orphan or anonymise.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('mod_id')->constrained('etui_mods')->restrictOnDelete();
            $table->string('name', 255);
            $table->longText('content');
            $table->char('share_token', 32)->unique();
            $table->boolean('is_public')->default(false);
            $table->timestamp('last_saved_at')->nullable();
            $table->enum('parse_status', ['pending', 'clean', 'errors', 'unknown'])
                ->default('pending');
            $table->integer('parse_error_count')->default(0);
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etui_projects');
    }
};
