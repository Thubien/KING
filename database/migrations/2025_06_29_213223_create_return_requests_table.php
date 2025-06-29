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
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('store_id')->constrained();
            $table->string('order_number');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('product_name');
            $table->text('return_reason');
            $table->enum('status', ['pending', 'in_transit', 'processing', 'completed'])->default('pending');
            $table->enum('resolution', ['refund', 'exchange', 'store_credit', 'rejected'])->nullable();
            $table->text('notes')->nullable();
            $table->string('tracking_number')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index(['store_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
