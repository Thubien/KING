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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');

            // Item details
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Stock levels
            $table->integer('quantity')->default(0);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('total_value', 12, 2)->default(0);

            // Additional info
            $table->string('supplier')->nullable();
            $table->string('category')->nullable();
            $table->string('location')->nullable();

            // Reorder management
            $table->integer('reorder_point')->default(0);
            $table->integer('reorder_quantity')->default(0);

            // Tracking
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamp('last_counted_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['store_id', 'is_active']);
            $table->index(['store_id', 'category']);
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
