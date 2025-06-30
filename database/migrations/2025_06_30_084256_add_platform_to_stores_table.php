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
            $table->enum('platform', ['shopify', 'physical', 'boutique', 'other'])
                  ->default('shopify')
                  ->after('name');
        });

        // Mevcut verileri güncelle
        \DB::table('stores')->update(['platform' => 'shopify']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
};
