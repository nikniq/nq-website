<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->index();
            $table->timestamp('plus_until')->nullable(); // cached from the active subscription
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_subscription_id')->unique();
            $table->string('stripe_price_id')->nullable();
            $table->string('plan', 32);
            $table->string('interval', 16)->nullable();
            $table->string('status', 32);
            $table->timestamp('current_period_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // Sales ledger. Kept when a user is deleted (user_id set to null).
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('amount_refunded')->default(0);
            $table->string('currency', 8);
            $table->string('plan', 32)->nullable();
            $table->string('interval', 16)->nullable();
            $table->string('status', 16)->default('paid'); // paid | refunded | partially_refunded
            $table->string('customer_email')->nullable();
            $table->timestamp('paid_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['stripe_customer_id']);
            $table->dropColumn(['stripe_customer_id', 'plus_until']);
        });
    }
};
