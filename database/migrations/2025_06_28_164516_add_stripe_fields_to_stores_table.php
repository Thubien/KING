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
        Schema::table('stores', function (Blueprint $table) {
            // Stripe API Integration Fields (Premium Feature)
            $table->text('stripe_secret_key')->nullable()->after('settings');
            $table->string('stripe_publishable_key')->nullable()->after('stripe_secret_key');
            $table->boolean('stripe_sync_enabled')->default(false)->after('stripe_publishable_key');
            $table->timestamp('last_stripe_sync')->nullable()->after('stripe_sync_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_secret_key',
                'stripe_publishable_key', 
                'stripe_sync_enabled',
                'last_stripe_sync'
            ]);
        });
    }
};
