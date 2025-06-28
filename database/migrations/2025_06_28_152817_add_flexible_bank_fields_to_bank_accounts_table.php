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
        Schema::table('bank_accounts', function (Blueprint $table) {
            // Flexible bank information
            $table->string('bank_name')->nullable()->after('bank_type'); // Custom bank name
            $table->string('bank_branch')->nullable()->after('bank_name'); // Branch name/location
            $table->string('country_code', 2)->default('US')->after('bank_branch'); // ISO country code
            $table->string('bank_address')->nullable()->after('country_code'); // Bank address
            $table->string('bank_phone')->nullable()->after('bank_address'); // Bank contact
            $table->string('bank_website')->nullable()->after('bank_phone'); // Bank website
            
            // Additional identifiers for international banks
            $table->string('bic_code')->nullable()->after('swift_code'); // Bank Identifier Code
            $table->string('sort_code')->nullable()->after('bic_code'); // UK sort code
            $table->string('bsb_number')->nullable()->after('sort_code'); // Australia BSB
            $table->string('institution_number')->nullable()->after('bsb_number'); // Canada
            $table->string('bank_code')->nullable()->after('institution_number'); // General bank code
            
            // Custom field support for any banking system
            $table->json('custom_fields')->nullable()->after('bank_code'); // Flexible custom fields
            
            // Modify existing bank_type to be more flexible
            $table->string('bank_type')->change(); // Remove enum constraint, allow any string
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'bank_branch', 
                'country_code',
                'bank_address',
                'bank_phone',
                'bank_website',
                'bic_code',
                'sort_code',
                'bsb_number',
                'institution_number',
                'bank_code',
                'custom_fields'
            ]);
        });
    }
};