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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            
            // Movement details
            $table->enum('movement_type', ['IN', 'OUT', 'SALE', 'RETURN', 'ADJUST', 'COUNT', 'DAMAGE', 'TRANSFER']);
            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 12, 2);
            
            // Tracking
            $table->string('reason');
            $table->string('reference_number')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Before/After quantities
            $table->integer('old_quantity');
            $table->integer('new_quantity');
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['inventory_item_id', 'created_at']);
            $table->index(['transaction_id']);
            $table->index(['movement_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};