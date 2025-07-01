<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add customer_id to transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            $table->index(['customer_id', 'transaction_date']);
            $table->index(['customer_id', 'status']);
        });
        
        // Add customer_id to return_requests
        Schema::table('return_requests', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            $table->index(['customer_id', 'created_at']);
            $table->index(['customer_id', 'status']);
        });
        
        // Add customer_id to store_credits (if not already linked via return_request)
        Schema::table('store_credits', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('store_id')->constrained()->nullOnDelete();
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
        
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
        
        Schema::table('store_credits', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};