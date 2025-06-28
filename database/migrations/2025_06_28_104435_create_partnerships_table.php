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
        Schema::create('partnerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('ownership_percentage', 5, 2); // 0.00 to 100.00
            $table->enum('role', ['owner', 'partner', 'investor', 'manager'])->default('partner');
            $table->text('role_description')->nullable();
            $table->date('partnership_start_date');
            $table->date('partnership_end_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending', 'terminated'])->default('pending');
            $table->json('permissions')->nullable(); // Custom permissions per partnership
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Unique constraint: One user can only have one partnership per store
            $table->unique(['store_id', 'user_id']);
            
            // Indexes for performance
            $table->index(['store_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['ownership_percentage']);
            
            // Note: Check constraint will be added via database-level constraint or model validation
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partnerships');
    }
};
