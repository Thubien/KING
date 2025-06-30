<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_timeline_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            
            // Event Information
            $table->enum('event_type', [
                'order_placed',
                'order_completed',
                'order_cancelled',
                'return_requested',
                'return_completed',
                'payment_received',
                'store_credit_issued',
                'store_credit_used',
                'note_added',
                'tag_added',
                'tag_removed',
                'status_changed',
                'communication_sent',
                'customer_created',
                'address_added',
                'address_updated'
            ]);
            
            $table->string('event_title');
            $table->text('event_description')->nullable();
            $table->json('event_data')->nullable(); // Store relevant data
            
            // Related Model (Polymorphic)
            $table->string('related_model')->nullable(); // Transaction, ReturnRequest, StoreCredit, etc.
            $table->unsignedBigInteger('related_id')->nullable();
            
            // Who created this event
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['customer_id', 'created_at']);
            $table->index(['customer_id', 'event_type']);
            $table->index(['related_model', 'related_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_timeline_events');
    }
};