<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint', 100);
            $table->string('method', 10)->default('GET');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('client_type', 50)->nullable(); // discord_bot, telegram, curl, browser, unknown
            $table->unsignedSmallInteger('response_ms')->nullable();
            $table->unsignedSmallInteger('status_code')->default(200);
            $table->string('query_string', 500)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
