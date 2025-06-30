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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partnership_id')->constrained()->onDelete('cascade');
            $table->foreignId('initiated_by_user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->onDelete('restrict');

            $table->enum('settlement_type', [
                'payment',      // Partner pays back debt
                'withdrawal',   // Partner withdraws profit (creates debt)
                'expense',      // Personal expense settlement
                'adjustment',   // Manual adjustment
                'profit_share',  // Profit distribution
            ]);

            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->text('description')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();

            $table->timestamp('settled_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->decimal('debt_balance_before', 12, 2)->nullable();
            $table->decimal('debt_balance_after', 12, 2)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['partnership_id', 'status']);
            $table->index(['initiated_by_user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
