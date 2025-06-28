<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'company_id',
        'initiated_by',
        'import_type',
        'source_type',
        'original_filename',
        'file_path',
        'file_size',
        'file_hash',
        'mime_type',
        'status',
        'total_records',
        'processed_records',
        'successful_records',
        'failed_records',
        'duplicate_records',
        'skipped_records',
        'started_at',
        'completed_at',
        'processing_time_seconds',
        'import_settings',
        'metadata',
        'results_summary',
        'errors',
        'error_message',
        'total_amount',
        'currency',
        'requires_review',
        'notes',
    ];

    protected $casts = [
        'import_settings' => 'array',
        'metadata' => 'array',
        'results_summary' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'requires_review' => 'boolean',
    ];

    // Boot method for auto-generation and scoping
    protected static function boot()
    {
        parent::boot();
        
        // Multi-tenant scoping
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && auth()->user()->company_id) {
                $builder->where('company_id', auth()->user()->company_id);
            }
        });

        static::creating(function ($batch) {
            if (!$batch->batch_id) {
                $batch->batch_id = 'IMP-' . strtoupper(Str::random(8));
            }
            
            if (!$batch->company_id && auth()->check()) {
                $batch->company_id = auth()->user()->company_id;
            }
            
            if (!$batch->initiated_by && auth()->check()) {
                $batch->initiated_by = auth()->id();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'import_batch_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRequiresReview($query)
    {
        return $query->where('requires_review', true);
    }

    // Status Management Methods
    public function markAsProcessing(): self
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
        
        return $this;
    }

    public function markAsCompleted(): self
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'processing_time_seconds' => $this->started_at ? 
                now()->diffInSeconds($this->started_at) : null,
        ]);
        
        return $this;
    }

    public function markAsFailed(string $errorMessage, array $errors = []): self
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'errors' => $errors,
            'processing_time_seconds' => $this->started_at ? 
                now()->diffInSeconds($this->started_at) : null,
        ]);
        
        return $this;
    }

    // Progress Tracking Methods
    public function updateProgress(array $counts): self
    {
        $this->update($counts);
        return $this;
    }

    public function incrementSuccessful(int $count = 1): self
    {
        $this->increment('successful_records', $count);
        $this->increment('processed_records', $count);
        return $this;
    }

    public function incrementFailed(int $count = 1): self
    {
        $this->increment('failed_records', $count);
        $this->increment('processed_records', $count);
        return $this;
    }

    public function incrementDuplicates(int $count = 1): self
    {
        $this->increment('duplicate_records', $count);
        $this->increment('processed_records', $count);
        return $this;
    }

    public function incrementSkipped(int $count = 1): self
    {
        $this->increment('skipped_records', $count);
        $this->increment('processed_records', $count);
        return $this;
    }

    // Business Logic Methods
    public function getProgressPercentage(): float
    {
        if ($this->total_records === 0) {
            return 0;
        }
        
        return round(($this->processed_records / $this->total_records) * 100, 2);
    }

    public function getSuccessRate(): float
    {
        if ($this->processed_records === 0) {
            return 0;
        }
        
        return round(($this->successful_records / $this->processed_records) * 100, 2);
    }

    public function isInProgress(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors) || !empty($this->error_message);
    }

    public function getFormattedFileSize(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }
        
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDurationFormatted(): string
    {
        if (!$this->processing_time_seconds) {
            return 'N/A';
        }
        
        $seconds = $this->processing_time_seconds;
        
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60, 1) . 'm';
        } else {
            return round($seconds / 3600, 1) . 'h';
        }
    }

    // Validation and Security
    public function canBeProcessed(): bool
    {
        return $this->status === 'pending' && !empty($this->file_path);
    }

    public function canBeReprocessed(): bool
    {
        return in_array($this->status, ['failed', 'completed']) && 
               auth()->user()->hasStoreAccess($this->company_id);
    }

    // Helper Methods
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['progress_percentage'] = $this->getProgressPercentage();
        $array['success_rate'] = $this->getSuccessRate();
        $array['formatted_file_size'] = $this->getFormattedFileSize();
        $array['duration_formatted'] = $this->getDurationFormatted();
        $array['is_in_progress'] = $this->isInProgress();
        $array['is_completed'] = $this->isCompleted();
        $array['has_failed'] = $this->hasFailed();
        $array['has_errors'] = $this->hasErrors();
        
        return $array;
    }
}
