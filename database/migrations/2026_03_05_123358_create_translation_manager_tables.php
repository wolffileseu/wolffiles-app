<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tm_languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->string('flag', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tm_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('en');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tm_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tm_key_id')->constrained('tm_keys')->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->text('value')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamp('translated_at')->nullable();
            $table->timestamps();
            $table->unique(['tm_key_id', 'language_code']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('tm_translations');
        Schema::dropIfExists('tm_keys');
        Schema::dropIfExists('tm_languages');
    }
};
