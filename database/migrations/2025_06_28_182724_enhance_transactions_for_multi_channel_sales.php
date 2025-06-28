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
        Schema::table('transactions', function (Blueprint $table) {
            // Sales Channel (WHERE sale happened)
            $table->enum('sales_channel', [
                'shopify', 'instagram', 'telegram', 'whatsapp', 
                'facebook', 'physical', 'referral', 'other'
            ])->default('shopify')->after('source');
            
            // Payment Method (HOW customer paid)
            $table->enum('payment_method', [
                'cash', 'credit_card', 'bank_transfer', 'cash_on_delivery',
                'cargo_collect', 'crypto', 'installment', 'store_credit', 'other'
            ])->nullable()->after('sales_channel');
            
            // Data Source (FROM WHERE to system) - rename existing 'source' field concept
            $table->enum('data_source', [
                'shopify_api', 'stripe_api', 'paypal_api', 
                'manual_entry', 'csv_import', 'webhook'
            ])->default('manual_entry')->after('payment_method');
            
            // Customer Info (for manual orders)
            $table->json('customer_info')->nullable()->after('data_source');
            
            // Sales Rep (who made the sale)
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->after('customer_info');
            
            // Order Details
            $table->text('order_notes')->nullable()->after('sales_rep_id');
            $table->string('order_reference')->nullable()->after('order_notes'); // Instagram post link, etc.
            
            // Add indexes for performance
            $table->index(['sales_channel', 'transaction_date']);
            $table->index(['sales_rep_id', 'transaction_date']);
            $table->index(['data_source', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['sales_channel', 'transaction_date']);
            $table->dropIndex(['sales_rep_id', 'transaction_date']);
            $table->dropIndex(['data_source', 'created_at']);
            
            $table->dropForeign(['sales_rep_id']);
            $table->dropColumn([
                'sales_channel',
                'payment_method', 
                'data_source',
                'customer_info',
                'sales_rep_id',
                'order_notes',
                'order_reference'
            ]);
        });
    }
};
