<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etui_revisions', function (Blueprint $table) {
            $table->id();
            // cascadeOnDelete: a revision is bound to its project — when the
            // project goes, the revision history goes with it.
            $table->foreignId('project_id')->constrained('etui_projects')->cascadeOnDelete();
            $table->longText('content');
            // A revision is immutable — only created_at, no updated_at.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etui_revisions');
    }
};
