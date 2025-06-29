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
        Schema::table('return_requests', function (Blueprint $table) {
            $table->string('product_sku')->nullable()->after('product_name');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('product_sku');
            $table->string('currency', 3)->default('USD')->after('refund_amount');
            $table->string('customer_tracking_number')->nullable()->after('tracking_number');
            $table->json('media')->nullable()->after('notes');
            $table->integer('quantity')->default(1)->after('product_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropColumn(['product_sku', 'refund_amount', 'currency', 'customer_tracking_number', 'media', 'quantity']);
        });
    }
};
