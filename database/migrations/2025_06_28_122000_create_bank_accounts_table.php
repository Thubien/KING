<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('bank_type'); // MERCURY, PAYONEER, CHASE, etc.
            $table->string('account_name')->nullable();
            $table->text('account_number')->nullable(); // Encrypted
            $table->text('routing_number')->nullable(); // Encrypted
            $table->string('iban')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['company_id', 'is_primary']);
            $table->index(['bank_type', 'currency']);
            $table->index(['is_active', 'is_primary']);

            // Ensure only one primary account per company
            $table->unique(['company_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
