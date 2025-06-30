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
        Schema::table('partnerships', function (Blueprint $table) {
            $table->string('partner_email')->nullable()->after('user_id');
            $table->string('invitation_token', 64)->nullable()->unique()->after('partner_email');
            $table->enum('status', ['PENDING_INVITATION', 'ACTIVE', 'INACTIVE'])->default('ACTIVE')->change();
            $table->timestamp('invited_at')->nullable()->after('invitation_token');
            $table->timestamp('activated_at')->nullable()->after('invited_at');

            $table->index('invitation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partnerships', function (Blueprint $table) {
            $table->dropColumn(['partner_email', 'invitation_token', 'invited_at', 'activated_at']);
            $table->dropIndex(['invitation_token']);
            $table->enum('status', ['active', 'inactive', 'pending', 'terminated'])->default('pending')->change();
        });
    }
};
