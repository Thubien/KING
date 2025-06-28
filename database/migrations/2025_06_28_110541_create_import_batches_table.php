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
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->unique(); // Human-readable batch identifier
            
            // Multi-tenant and user tracking
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('initiated_by')->constrained('users')->onDelete('restrict');
            
            // Import type and source
            $table->enum('import_type', ['csv', 'shopify', 'stripe', 'paypal', 'manual', 'api'])->default('csv');
            $table->enum('source_type', ['payoneer', 'mercury', 'stripe', 'shopify_payments', 'bank', 'other'])->nullable();
            
            // File information
            $table->string('original_filename')->nullable();
            $table->string('file_path')->nullable(); // Stored file path
            $table->integer('file_size')->nullable(); // File size in bytes
            $table->string('file_hash')->nullable(); // For duplicate detection
            $table->string('mime_type')->nullable();
            
            // Status and progress tracking
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('successful_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->integer('duplicate_records')->default(0);
            $table->integer('skipped_records')->default(0);
            
            // Processing details
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('processing_time_seconds')->nullable(); // Total processing time
            
            // Import settings and metadata
            $table->json('import_settings')->nullable(); // Store-specific import settings
            $table->json('metadata')->nullable(); // Additional metadata (currency, date range, etc.)
            
            // Results and errors
            $table->json('results_summary')->nullable(); // Summary of import results
            $table->json('errors')->nullable(); // Array of error messages
            $table->text('error_message')->nullable(); // Primary error message if failed
            
            // Validation and audit
            $table->decimal('total_amount', 15, 2)->nullable(); // Total transaction amount for validation
            $table->string('currency', 3)->nullable(); // Primary currency of the import
            $table->boolean('requires_review')->default(false); // Flag for manual review needed
            $table->text('notes')->nullable(); // Additional notes
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['import_type', 'source_type']);
            $table->index(['initiated_by', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['file_hash']); // For duplicate file detection
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
