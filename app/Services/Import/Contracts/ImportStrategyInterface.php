<?php

namespace App\Services\Import\Contracts;

use App\Models\ImportBatch;
use App\Services\Import\ImportResult;

interface ImportStrategyInterface
{
    /**
     * Validate the import data before processing
     */
    public function validate($data): array;

    /**
     * Process the import data and create transactions
     */
    public function process(ImportBatch $batch, $data): ImportResult;

    /**
     * Detect the source type from the data
     */
    public function detectSource($data): ?string;

    /**
     * Get supported file extensions for this strategy
     */
    public function getSupportedExtensions(): array;

    /**
     * Get the strategy name/identifier
     */
    public function getName(): string;

    /**
     * Check if this strategy can handle the given data
     */
    public function canHandle($data): bool;
} 