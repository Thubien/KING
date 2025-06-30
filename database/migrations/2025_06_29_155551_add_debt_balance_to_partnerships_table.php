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
        Schema::table('partnerships', function (Blueprint $table) {
            // Partner's debt balance tracking
            $table->decimal('debt_balance', 12, 2)->default(0)->after('ownership_percentage')
                ->comment('Partner debt balance (positive = owes money, negative = has credit)');

            // Track last debt update for audit
            $table->timestamp('debt_last_updated_at')->nullable()->after('debt_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partnerships', function (Blueprint $table) {
            $table->dropColumn(['debt_balance', 'debt_last_updated_at']);
        });
    }
};
