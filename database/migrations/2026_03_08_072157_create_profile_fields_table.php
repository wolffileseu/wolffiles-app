<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('profile_fields', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('key')->unique();
            $table->enum('type', ['text','url','textarea','select'])->default('text');
            $table->string('placeholder')->nullable();
            $table->json('options')->nullable()->comment('Für select-Typ');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_profile')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('profile_data')->nullable()->after('notification_preferences');
        });
    }
    public function down(): void {
        Schema::dropIfExists('profile_fields');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_data');
        });
    }
};
