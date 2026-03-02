<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('server_orders')->cascadeOnDelete();
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('period', ['daily', 'weekly', 'monthly', 'quarterly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable();     // paypal/stripe/bank
            $table->string('payment_transaction_id')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_invoices');
    }
};
