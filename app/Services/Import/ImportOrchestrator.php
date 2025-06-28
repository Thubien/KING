<?php

namespace App\Services\Import;

use App\Models\ImportBatch;
use App\Services\Import\Contracts\ImportStrategyInterface;
use App\Services\Import\Strategies\CsvImportStrategy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ImportOrchestrator
{
    protected array $strategies = [];

    public function __construct()
    {
        // Register available import strategies
        $this->registerStrategies();
    }

    /**
     * Register all available import strategies
     */
    protected function registerStrategies(): void
    {
        // Register CSV import strategy
        $this->strategies['csv'] = new CsvImportStrategy();
        
        // TODO: Register additional strategies as they are created
        // $this->strategies['shopify'] = app(ShopifyImportStrategy::class);
        // $this->strategies['stripe'] = app(StripeImportStrategy::class);
    }

    /**
     * Register a new import strategy
     */
    public function registerStrategy(string $name, ImportStrategyInterface $strategy): self
    {
        $this->strategies[$name] = $strategy;
        return $this;
    }

    /**
     * Import data using the appropriate strategy
     */
    public function import(
        string $importType,
        $data,
        array $options = []
    ): ImportResult {
        try {
            // Create import batch for tracking
            $batch = $this->createImportBatch($importType, $data, $options);
            
            // Find appropriate strategy
            $strategy = $this->findStrategy($importType, $data);
            
            if (!$strategy) {
                $errorMessage = "No suitable import strategy found for type: {$importType}";
                $batch->markAsFailed($errorMessage);
                return ImportResult::failure($errorMessage);
            }

            // Mark batch as processing
            $batch->markAsProcessing();

            // Validate data before processing
            $validationResult = $strategy->validate($data);
            if (!empty($validationResult)) {
                $errorMessage = "Data validation failed";
                $batch->markAsFailed($errorMessage, $validationResult);
                return ImportResult::failure($errorMessage, $validationResult);
            }

            // Process the import
            $result = $this->processImport($batch, $strategy, $data);

            // Update batch with results
            $this->updateBatchResults($batch, $result);

            return $result;

        } catch (Exception $e) {
            Log::error('Import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'import_type' => $importType,
            ]);

            if (isset($batch)) {
                $batch->markAsFailed($e->getMessage());
            }

            return ImportResult::failure($e->getMessage());
        }
    }

    /**
     * Create import batch for tracking
     */
    protected function createImportBatch(
        string $importType,
        $data,
        array $options = []
    ): ImportBatch {
        $batchData = [
            'import_type' => $importType,
            'status' => 'pending',
        ];

        // Handle file uploads
        if (isset($options['file'])) {
            $file = $options['file'];
            $filename = $file->getClientOriginalName();
            $path = $file->store('imports');
            
            $batchData = array_merge($batchData, [
                'original_filename' => $filename,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_hash' => hash_file('md5', $file->getRealPath()),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        // Add metadata from options
        if (isset($options['metadata'])) {
            $batchData['metadata'] = $options['metadata'];
        }

        if (isset($options['source_type'])) {
            $batchData['source_type'] = $options['source_type'];
        }

        return ImportBatch::create($batchData);
    }

    /**
     * Find the appropriate import strategy
     */
    protected function findStrategy(string $importType, $data): ?ImportStrategyInterface
    {
        // First, try to find by import type
        if (isset($this->strategies[$importType])) {
            $strategy = $this->strategies[$importType];
            if ($strategy->canHandle($data)) {
                return $strategy;
            }
        }

        // If not found, try all strategies to see which can handle the data
        foreach ($this->strategies as $strategy) {
            if ($strategy->canHandle($data)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * Process the import using the strategy
     */
    protected function processImport(
        ImportBatch $batch,
        ImportStrategyInterface $strategy,
        $data
    ): ImportResult {
        DB::beginTransaction();

        try {
            // Detect source type if not already set
            if (!$batch->source_type) {
                $sourceType = $strategy->detectSource($data);
                if ($sourceType) {
                    $batch->update(['source_type' => $sourceType]);
                }
            }

            // Process the import
            $result = $strategy->process($batch, $data);

            if ($result->success) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            return $result;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update batch with import results
     */
    protected function updateBatchResults(ImportBatch $batch, ImportResult $result): void
    {
        $updateData = [
            'total_records' => $result->totalRecords,
            'successful_records' => $result->successfulRecords,
            'failed_records' => $result->failedRecords,
            'duplicate_records' => $result->duplicateRecords,
            'skipped_records' => $result->skippedRecords,
            'results_summary' => $result->summary,
            'metadata' => array_merge($batch->metadata ?? [], $result->metadata),
        ];

        if ($result->success) {
            $batch->update($updateData);
            $batch->markAsCompleted();
        } else {
            $updateData['errors'] = $result->errors;
            $updateData['error_message'] = $result->errorMessage;
            $batch->update($updateData);
            $batch->markAsFailed($result->errorMessage ?? 'Import failed', $result->errors);
        }
    }

    /**
     * Get all registered strategies
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Check if a file has already been imported
     */
    public function isDuplicateFile(string $fileHash): bool
    {
        return ImportBatch::where('file_hash', $fileHash)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Get import history for the current company
     */
    public function getImportHistory(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return ImportBatch::with(['initiator', 'company'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Reprocess a failed import
     */
    public function reprocessImport(ImportBatch $batch): ImportResult
    {
        if (!$batch->canBeReprocessed()) {
            return ImportResult::failure('Import cannot be reprocessed');
        }

        // Reset batch status
        $batch->update([
            'status' => 'pending',
            'processed_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'duplicate_records' => 0,
            'skipped_records' => 0,
            'errors' => null,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'processing_time_seconds' => null,
        ]);

        // Get file data and reprocess
        if ($batch->file_path && Storage::exists($batch->file_path)) {
            $fileContent = Storage::get($batch->file_path);
            return $this->import($batch->import_type, $fileContent, [
                'existing_batch' => $batch,
                'source_type' => $batch->source_type,
            ]);
        }

        return ImportResult::failure('Import file not found');
    }
} 