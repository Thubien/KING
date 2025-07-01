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
        if (!Schema::hasTable('user_login_logs')) {
            Schema::create('user_login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address', 45);
            $table->string('user_agent');
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('location')->nullable(); // city, country
            $table->boolean('is_successful')->default(true);
            $table->string('failure_reason')->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('logged_out_at')->nullable();
            
            $table->index(['user_id', 'logged_in_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_login_logs');
    }
};
