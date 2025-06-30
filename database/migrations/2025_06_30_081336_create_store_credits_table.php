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
        Schema::create('store_credits', function (Blueprint $table) {
            $table->id();
            
            // Temel bilgiler
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('return_request_id')->nullable();
            
            // Store credit detayları
            $table->string('code')->unique()
                  ->comment('Benzersiz store credit kodu');
            
            $table->decimal('amount', 10, 2)
                  ->comment('Toplam store credit tutarı');
            
            $table->decimal('remaining_amount', 10, 2)
                  ->comment('Kalan kullanılabilir tutar');
            
            $table->string('currency', 3)->default('TRY');
            
            // Müşteri bilgileri
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            
            // Durum bilgileri
            $table->enum('status', ['active', 'partially_used', 'fully_used', 'expired', 'cancelled'])
                  ->default('active');
            
            // Tarihler
            $table->datetime('issued_at')->useCurrent();
            $table->datetime('expires_at')->nullable();
            $table->datetime('used_at')->nullable();
            $table->datetime('last_used_at')->nullable();
            
            // Kullanım geçmişi (JSON)
            $table->json('usage_history')->nullable()
                  ->comment('Kullanım detayları [{date, amount, transaction_id, remaining}]');
            
            // Notlar
            $table->text('notes')->nullable();
            $table->string('issued_by')->nullable()
                  ->comment('Store credit\'i oluşturan kullanıcı');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
            $table->foreign('return_request_id')->references('id')->on('return_requests')->nullOnDelete();
            
            // Indexes
            $table->index('code');
            $table->index('status');
            $table->index('customer_email');
            $table->index(['company_id', 'store_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_credits');
    }
};