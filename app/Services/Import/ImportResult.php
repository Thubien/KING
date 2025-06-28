<?php

namespace App\Services\Import;

class ImportResult
{
    public function __construct(
        public bool $success = true,
        public int $totalRecords = 0,
        public int $successfulRecords = 0,
        public int $failedRecords = 0,
        public int $duplicateRecords = 0,
        public int $skippedRecords = 0,
        public array $errors = [],
        public ?string $errorMessage = null,
        public array $summary = [],
        public array $metadata = []
    ) {}

    public static function success(
        int $totalRecords = 0,
        int $successfulRecords = 0,
        int $failedRecords = 0,
        int $duplicateRecords = 0,
        int $skippedRecords = 0,
        array $summary = [],
        array $metadata = []
    ): self {
        return new self(
            success: true,
            totalRecords: $totalRecords,
            successfulRecords: $successfulRecords,
            failedRecords: $failedRecords,
            duplicateRecords: $duplicateRecords,
            skippedRecords: $skippedRecords,
            summary: $summary,
            metadata: $metadata
        );
    }

    public static function failure(
        string $errorMessage,
        array $errors = [],
        array $metadata = []
    ): self {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            errors: $errors,
            metadata: $metadata
        );
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors) || !empty($this->errorMessage);
    }

    public function getProcessedRecords(): int
    {
        return $this->successfulRecords + $this->failedRecords + $this->duplicateRecords + $this->skippedRecords;
    }

    public function getSuccessRate(): float
    {
        $processed = $this->getProcessedRecords();
        
        if ($processed === 0) {
            return 0;
        }
        
        return round(($this->successfulRecords / $processed) * 100, 2);
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'total_records' => $this->totalRecords,
            'successful_records' => $this->successfulRecords,
            'failed_records' => $this->failedRecords,
            'duplicate_records' => $this->duplicateRecords,
            'skipped_records' => $this->skippedRecords,
            'processed_records' => $this->getProcessedRecords(),
            'success_rate' => $this->getSuccessRate(),
            'errors' => $this->errors,
            'error_message' => $this->errorMessage,
            'summary' => $this->summary,
            'metadata' => $this->metadata,
        ];
    }
} 