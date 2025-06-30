<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_addresses')) {
            Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            
            $table->enum('type', ['billing', 'shipping', 'both'])->default('both');
            $table->string('label')->nullable(); // "Ev", "İş", "Depo" etc.
            $table->boolean('is_default')->default(false);
            
            // Address Details
            $table->string('full_name'); // Might be different from customer name
            $table->string('phone')->nullable(); // Delivery contact
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('district')->nullable(); // İlçe
            $table->string('city');
            $table->string('state_province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('TR');
            
            // Additional Info
            $table->text('delivery_notes')->nullable(); // "Kapıcıya bırakın" etc.
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['customer_id', 'type']);
            $table->index(['customer_id', 'is_default']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};