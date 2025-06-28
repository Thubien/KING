<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Feature Limits
            $table->boolean('api_integrations_enabled')->default(false)->after('trial_ends_at');
            $table->boolean('webhooks_enabled')->default(false)->after('api_integrations_enabled');
            $table->boolean('real_time_sync_enabled')->default(false)->after('webhooks_enabled');
            
            // Usage Tracking
            $table->integer('api_calls_this_month')->default(0)->after('real_time_sync_enabled');
            $table->integer('max_api_calls_per_month')->default(0)->after('api_calls_this_month');
            
            // Billing
            $table->string('stripe_customer_id')->nullable()->after('max_api_calls_per_month');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');
            $table->timestamp('last_payment_at')->nullable()->after('stripe_subscription_id');
            $table->timestamp('next_billing_date')->nullable()->after('last_payment_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'api_integrations_enabled',
                'webhooks_enabled',
                'real_time_sync_enabled',
                'api_calls_this_month',
                'max_api_calls_per_month',
                'stripe_customer_id',
                'stripe_subscription_id',
                'last_payment_at',
                'next_billing_date'
            ]);
        });
    }
};