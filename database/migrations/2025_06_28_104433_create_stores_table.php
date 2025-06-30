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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('shopify_domain')->unique(); // example.myshopify.com
            $table->string('shopify_store_id')->nullable(); // Shopify internal ID
            $table->string('shopify_access_token')->nullable(); // Encrypted in model
            $table->string('currency', 3)->default('USD');
            $table->string('country_code', 2)->nullable();
            $table->string('timezone')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('shopify_webhook_endpoints')->nullable();
            $table->enum('status', ['active', 'inactive', 'connecting', 'error'])->default('connecting');
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_errors')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['shopify_domain']);
            $table->index(['last_sync_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
