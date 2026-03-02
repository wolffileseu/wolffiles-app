<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('server_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');                         // created/started/stopped/restarted/config_changed/mod_changed/map_added/suspended/terminated/renewed
            $table->json('details')->nullable();
            $table->string('performed_by')->default('user');  // user/system/admin
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at');

            $table->index(['order_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_activity_logs');
    }
};
