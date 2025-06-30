<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_processor_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('processor_type'); // STRIPE, PAYPAL, SHOPIFY_PAYMENTS, MANUAL
            $table->string('account_identifier')->nullable(); // account_id, email, etc.
            $table->string('currency', 3)->default('USD');
            $table->decimal('current_balance', 15, 2)->default(0); // Çekilebilir para
            $table->decimal('pending_balance', 15, 2)->default(0); // Beklemede olan para (KRITIK!)
            $table->decimal('pending_payouts', 15, 2)->default(0); // İşlemde olan transferler
            $table->json('metadata')->nullable(); // Ek bilgiler
            $table->timestamp('last_sync_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Performance indexes
            $table->index(['company_id', 'processor_type']);
            $table->index(['processor_type', 'currency']);
            $table->index(['is_active', 'processor_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_processor_accounts');
    }
};
