<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etui_files', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: dropping a mod would orphan 1000+ files —
            // safer to refuse the delete than silently cascade.
            $table->foreignId('mod_id')->constrained('etui_mods')->restrictOnDelete();
            $table->string('name', 255);
            $table->longText('content');
            $table->enum('source', ['stock', 'user'])->default('user');
            // nullOnDelete: keep the file but anonymise the uploader on user
            // deletion — matches the Wolffiles GDPR pattern for user content.
            $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_public')->default(true);
            $table->char('hash', 64);
            $table->enum('parse_status', ['pending', 'clean', 'errors', 'unknown'])
                ->default('pending');
            $table->integer('parse_error_count')->default(0);
            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();

            // Dedup key — same name + same content under same mod is the same file.
            $table->unique(['mod_id', 'name', 'hash']);
            $table->index('parse_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etui_files');
    }
};
