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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Bildirim Tercihleri
            $table->boolean('email_notifications')->default(true);
            $table->boolean('email_return_requests')->default(true);
            $table->boolean('email_large_transactions')->default(true);
            $table->decimal('email_transaction_threshold', 10, 2)->default(1000);
            $table->boolean('email_partner_activities')->default(true);
            $table->boolean('email_weekly_report')->default(true);
            $table->boolean('email_monthly_report')->default(false);
            
            $table->boolean('app_notifications')->default(true);
            $table->boolean('app_return_requests')->default(true);
            $table->boolean('app_large_transactions')->default(true);
            $table->boolean('app_partner_activities')->default(true);
            
            // Çalışma Tercihleri
            $table->string('default_currency', 3)->default('USD');
            $table->string('date_format')->default('d/m/Y');
            $table->string('time_format')->default('H:i');
            $table->integer('records_per_page')->default(25);
            $table->string('timezone')->default('Europe/Istanbul');
            $table->foreignId('default_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('notification_language', 2)->default('tr');
            
            $table->timestamps();
            
            // Unique index
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
