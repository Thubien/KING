<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            
            // Basic Information
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('birth_date')->nullable();
            
            // Customer Metadata
            $table->json('tags')->nullable(); // ['vip', 'wholesale', 'problematic', etc.]
            $table->text('notes')->nullable();
            $table->enum('source', ['manual', 'shopify', 'return', 'import'])->default('manual');
            $table->enum('status', ['active', 'inactive', 'blacklist'])->default('active');
            
            // Store-specific Statistics (Denormalized for performance)
            $table->date('first_order_date')->nullable();
            $table->date('last_order_date')->nullable();
            $table->integer('total_orders')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->decimal('total_spent_usd', 12, 2)->default(0); // For multi-currency
            $table->integer('total_returns')->default(0);
            $table->decimal('total_return_amount', 12, 2)->default(0);
            $table->decimal('avg_order_value', 10, 2)->default(0);
            $table->decimal('lifetime_value', 12, 2)->default(0); // LTV calculation
            
            // Communication Preferences
            $table->boolean('accepts_marketing')->default(true);
            $table->enum('preferred_contact_method', ['phone', 'whatsapp', 'email', 'sms'])->nullable();
            $table->string('whatsapp_number')->nullable(); // Might be different from phone
            
            // Additional Fields
            $table->string('tax_number')->nullable(); // For B2B customers
            $table->string('company_name')->nullable(); // For B2B customers
            $table->json('metadata')->nullable(); // For future extensibility
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'store_id']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'email']);
            $table->index(['store_id', 'phone']);
            $table->index('last_order_date');
            $table->index('total_spent');
            $table->index('created_at');
            
            // Unique constraint per store
            $table->unique(['store_id', 'email']);
            $table->unique(['store_id', 'phone']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};