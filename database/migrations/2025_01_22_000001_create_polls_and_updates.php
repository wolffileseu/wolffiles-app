<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->boolean('is_active')->default(true);
            $table->boolean('multiple_choice')->default(false);
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->string('text');
            $table->unsignedInteger('votes_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['poll_id', 'user_id', 'poll_option_id']);
        });

        // #28 Add trusted uploader flag
        if (!Schema::hasColumn('users', 'is_trusted_uploader')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_trusted_uploader')->default(false);
            });
        }

        // #33 Add report resolution fields
        if (!Schema::hasColumn('reports', 'resolved_by')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->foreignId('resolved_by')->nullable()->after('status');
                $table->timestamp('resolved_at')->nullable()->after('resolved_by');
                $table->text('admin_notes')->nullable()->after('resolved_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('poll_options');
        Schema::dropIfExists('polls');

        if (Schema::hasColumn('users', 'is_trusted_uploader')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_trusted_uploader');
            });
        }

        if (Schema::hasColumn('reports', 'resolved_by')) {
            Schema::table('reports', function (Blueprint $table) {
                $table->dropColumn(['resolved_by', 'resolved_at', 'admin_notes']);
            });
        }
    }
};
