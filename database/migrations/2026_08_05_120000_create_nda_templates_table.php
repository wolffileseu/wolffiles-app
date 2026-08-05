<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nda_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('locale', 5)->index();
            $table->unsignedInteger('version')->default(1);
            $table->longText('body');
            $table->boolean('is_active')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['locale', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nda_templates');
    }
};
