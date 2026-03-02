<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('donor_name')->nullable();
            $table->string('donor_email')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('message')->nullable();
            $table->string('source')->default('paypal'); // paypal, stripe, manual, other
            $table->string('transaction_id')->nullable()->unique();
            $table->string('status')->default('completed'); // pending, completed, refunded
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('show_on_wall')->default(true);
            $table->json('meta')->nullable(); // PayPal/Stripe raw data
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('user_id');
        });

        Schema::create('donation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Default settings
        DB::table('donation_settings')->insert([
            ['key' => 'monthly_goal', 'value' => '50', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'paypal_email', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'paypal_enabled', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'stripe_enabled', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'donation_message', 'value' => 'Help keep Wolffiles.eu running!', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'discord_webhook_url', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_settings');
        Schema::dropIfExists('donations');
    }
};
