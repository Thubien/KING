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
        if (!Schema::hasTable('transaction_learning_patterns')) {
            Schema::create('transaction_learning_patterns', function (Blueprint $table) {
                $table->id();
                $table->string('description_pattern');
                $table->enum('amount_type', ['income', 'expense']);
                $table->string('assigned_category', 50);
                $table->string('assigned_subcategory', 50)->nullable();
                $table->unsignedBigInteger('assigned_store_id')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->integer('confidence')->default(100);
                $table->integer('usage_count')->default(1);
                $table->timestamps();

                // Indexes for performance
                $table->index(['description_pattern', 'amount_type']);
                $table->index(['user_id', 'assigned_category']);
                $table->index('confidence');

                // Foreign keys
                $table->foreign('assigned_store_id')->references('id')->on('stores')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_learning_patterns');
    }
};
