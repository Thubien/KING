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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            
            // Transaction Details
            $table->string('transaction_id')->unique(); // Internal unique ID
            $table->string('external_id')->nullable(); // Shopify/Bank transaction ID
            $table->string('reference_number')->nullable(); // Invoice, order number, etc.
            
            // Financial Data
            $table->decimal('amount', 15, 2); // Supports up to 999,999,999,999.99
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 10, 6)->default(1.000000); // For currency conversion
            $table->decimal('amount_usd', 15, 2)->nullable(); // Converted amount for reporting
            
            // Category System (11 standard categories)
            $table->enum('category', [
                'revenue',           // Sales income
                'cost_of_goods',     // Product costs, inventory
                'marketing',         // Advertising, promotions
                'shipping',          // Fulfillment, delivery costs
                'fees_commissions',  // Payment processor, platform fees
                'taxes',             // VAT, sales tax, income tax
                'refunds_returns',   // Customer refunds, chargebacks
                'operational',       // Software subscriptions, utilities
                'partnerships',      // Partner payouts, profit sharing
                'investments',       // Capital injections, equipment
                'other'              // Miscellaneous transactions
            ]);
            
            $table->string('subcategory')->nullable(); // More specific categorization
            
            // Transaction Type
            $table->enum('type', ['income', 'expense']); // Determines if it adds or subtracts
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            
            // Descriptions and Notes
            $table->string('description'); // Brief description
            $table->text('notes')->nullable(); // Detailed notes
            $table->json('metadata')->nullable(); // Additional data from APIs
            
            // Dates
            $table->timestamp('transaction_date'); // When the transaction occurred
            $table->timestamp('processed_at')->nullable(); // When it was processed
            
            // Source tracking
            $table->enum('source', ['manual', 'shopify', 'stripe', 'paypal', 'bank', 'api'])->default('manual');
            $table->string('source_details')->nullable(); // Additional source info
            
            // Reconciliation
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['store_id', 'transaction_date']);
            $table->index(['store_id', 'category', 'type']);
            $table->index(['transaction_date', 'status']);
            $table->index(['currency', 'amount']);
            $table->index(['source', 'external_id']);
            $table->index(['is_reconciled', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
