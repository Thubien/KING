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
            // Transaction editor fields
            $table->enum('assignment_status', ['pending', 'assigned', 'split', 'matched'])->default('pending')->after('status');
            $table->text('user_notes')->nullable()->after('notes');
            $table->boolean('is_transfer')->default(false)->after('is_pending_payout');
            $table->unsignedBigInteger('matched_transaction_id')->nullable()->after('is_transfer');
            // Skip subcategory if it already exists
            if (! Schema::hasColumn('transactions', 'subcategory')) {
                $table->string('subcategory', 50)->nullable()->after('category');
            }

            // For multi-store split tracking
            $table->boolean('is_split')->default(false)->after('is_transfer');
            $table->unsignedBigInteger('parent_transaction_id')->nullable()->after('is_split');
            $table->decimal('split_percentage', 5, 2)->nullable()->after('parent_transaction_id');

            // Smart suggestions tracking
            $table->integer('suggestion_confidence')->default(0);
            $table->json('suggested_assignment')->nullable()->after('suggestion_confidence');

            // Indexes for performance
            $table->index('assignment_status');
            $table->index('matched_transaction_id');
            $table->index('parent_transaction_id');
            $table->index(['company_id', 'assignment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'assignment_status',
                'user_notes',
                'is_transfer',
                'matched_transaction_id',
                'subcategory',
                'is_split',
                'parent_transaction_id',
                'split_percentage',
                'suggestion_confidence',
                'suggested_assignment',
            ]);
        });
    }
};
