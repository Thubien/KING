<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Payment processor tracking
            $table->string('payment_processor_type')->nullable()->after('source_details');
            $table->foreignId('payment_processor_id')->nullable()->constrained('payment_processor_accounts')->after('payment_processor_type');

            // Payout tracking
            $table->boolean('is_pending_payout')->default(false)->after('is_reconciled');
            $table->timestamp('payout_date')->nullable()->after('is_pending_payout');

            // Personal expense tracking
            $table->boolean('is_personal_expense')->default(false)->after('payout_date');
            $table->foreignId('partner_id')->nullable()->constrained('users')->after('is_personal_expense');

            // Balance adjustments
            $table->boolean('is_adjustment')->default(false)->after('partner_id');
            $table->string('adjustment_type')->nullable()->after('is_adjustment');

            // Performance indexes
            $table->index(['payment_processor_type', 'is_pending_payout']);
            $table->index(['is_personal_expense', 'partner_id']);
            $table->index(['category', 'status', 'transaction_date']);
            $table->index(['is_adjustment', 'adjustment_type']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_processor_id']);
            $table->dropForeign(['partner_id']);

            $table->dropIndex(['payment_processor_type', 'is_pending_payout']);
            $table->dropIndex(['is_personal_expense', 'partner_id']);
            $table->dropIndex(['category', 'status', 'transaction_date']);
            $table->dropIndex(['is_adjustment', 'adjustment_type']);

            $table->dropColumn([
                'payment_processor_type',
                'payment_processor_id',
                'is_pending_payout',
                'payout_date',
                'is_personal_expense',
                'partner_id',
                'is_adjustment',
                'adjustment_type',
            ]);
        });
    }
};
