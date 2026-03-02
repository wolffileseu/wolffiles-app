<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('server_products')->nullOnDelete();
            $table->string('server_name');
            $table->enum('game', ['et', 'etl', 'rtcw']);
            $table->string('mod')->default('etmain');         // etmain/etpro/jaymod/nitmod/noquarter/silent
            $table->unsignedSmallInteger('slots');
            $table->string('pterodactyl_server_id')->nullable();
            $table->string('pterodactyl_user_id')->nullable();
            $table->enum('status', ['pending', 'provisioning', 'active', 'suspended', 'terminated', 'error'])->default('pending');
            $table->string('ip_address')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->text('rcon_password')->nullable();        // encrypted
            $table->string('server_password')->nullable();    // encrypted
            $table->enum('billing_period', ['daily', 'weekly', 'monthly', 'quarterly'])->default('monthly');
            $table->decimal('price_paid', 8, 2);
            $table->timestamp('paid_until')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->string('node')->nullable();               // Which Pterodactyl node
            $table->json('config')->nullable();               // Extra config overrides
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamp('last_status_check')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('paid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_orders');
    }
};
