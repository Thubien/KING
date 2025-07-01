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
            // İade yöntemi (nakit, değişim, store credit)
            $table->enum('refund_method', ['cash', 'exchange', 'store_credit'])
                  ->default('cash')
                  ->after('refund_amount');
            
            // Store credit bilgileri
            $table->decimal('store_credit_amount', 10, 2)->nullable()
                  ->after('refund_method')
                  ->comment('Store credit tutarı');
            
            $table->string('store_credit_code')->nullable()
                  ->after('store_credit_amount')
                  ->comment('Store credit kodu');
            
            // Finansal transaction bağlantısı
            $table->unsignedBigInteger('transaction_id')->nullable()
                  ->after('store_credit_code')
                  ->comment('İlişkili finansal transaction');
            
            // Finansal kayıt oluşturulacak mı?
            $table->boolean('creates_financial_record')->default(false)
                  ->after('transaction_id')
                  ->comment('Bu iade finansal kayıt oluşturacak mı?');
            
            // Değişim durumunda yeni ürün bilgileri
            $table->string('exchange_product_name')->nullable()
                  ->after('creates_financial_record')
                  ->comment('Değişim yapılan ürün adı');
            
            $table->string('exchange_product_sku')->nullable()
                  ->after('exchange_product_name')
                  ->comment('Değişim yapılan ürün SKU');
            
            $table->decimal('exchange_product_price', 10, 2)->nullable()
                  ->after('exchange_product_sku')
                  ->comment('Değişim yapılan ürün fiyatı');
            
            $table->decimal('exchange_difference', 10, 2)->nullable()
                  ->after('exchange_product_price')
                  ->comment('Değişim fark tutarı (+/-)');
            
            // Foreign key
            $table->foreign('transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->nullOnDelete();
            
            // Indexes for performance
            $table->index('refund_method');
            $table->index('store_credit_code');
            $table->index('creates_financial_record');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            
            $table->dropColumn([
                'refund_method',
                'store_credit_amount',
                'store_credit_code',
                'transaction_id',
                'creates_financial_record',
                'exchange_product_name',
                'exchange_product_sku',
                'exchange_product_price',
                'exchange_difference'
            ]);
        });
    }
};