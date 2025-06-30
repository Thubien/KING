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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('user_type', ['admin', 'company_owner', 'partner', 'viewer'])->default('partner');
            $table->json('preferences')->nullable(); // User UI preferences
            $table->timestamp('last_login_at')->nullable();
            $table->string('avatar_url')->nullable();
            $table->boolean('is_active')->default(true);

            // Indexes
            $table->index(['company_id', 'user_type']);
            $table->index(['company_id', 'is_active']);
            $table->index(['last_login_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn([
                'company_id',
                'user_type',
                'preferences',
                'last_login_at',
                'avatar_url',
                'is_active',
            ]);
        });
    }
};
