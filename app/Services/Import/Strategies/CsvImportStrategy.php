<?php

namespace App\Services\Import\Strategies;

use App\Models\ImportBatch;
use App\Models\Transaction;
use App\Services\Import\Contracts\ImportStrategyInterface;
use App\Services\Import\ImportResult;
use App\Services\Import\Detectors\BankFormatDetector;
use App\Services\Import\Parsers\DateParser;
use App\Services\Import\Parsers\AmountParser;
use Illuminate\Support\Facades\Log;
use Exception;

class CsvImportStrategy implements ImportStrategyInterface
{
    private BankFormatDetector $formatDetector;
    private DateParser $dateParser;
    private AmountParser $amountParser;

    public function __construct()
    {
        $this->formatDetector = new BankFormatDetector();
        $this->dateParser = new DateParser();
        $this->amountParser = new AmountParser();
    }

    /**
     * Validate the CSV data before processing
     */
    public function validate($data): array
    {
        $errors = [];

        try {
            // Parse CSV data
            $csvData = $this->parseCsvData($data);
            
            if (empty($csvData)) {
                $errors[] = 'CSV file is empty or could not be parsed';
                return $errors;
            }

            // Get headers and detect format
            $headers = array_keys($csvData[0]);
            $format = $this->formatDetector->detectFormat($headers);
            
            if ($format === BankFormatDetector::FORMAT_UNKNOWN) {
                $errors[] = 'Unknown CSV format. Supported formats: Mercury Bank, Payoneer, Stripe Balance, Stripe Payments';
                return $errors;
            }

            // Validate format-specific requirements
            $formatErrors = $this->formatDetector->validateFormat($headers, $format);
            $errors = array_merge($errors, $formatErrors);

        } catch (Exception $e) {
            $errors[] = "Failed to validate CSV: " . $e->getMessage();
        }

        return $errors;
    }

    /**
     * Process the CSV data and create transactions
     */
    public function process(ImportBatch $batch, $data): ImportResult
    {
        try {
            // Parse CSV data
            $csvData = $this->parseCsvData($data);
            
            if (empty($csvData)) {
                return ImportResult::failure('CSV file is empty or could not be parsed');
            }

            // Detect format
            $headers = array_keys($csvData[0]);
            $format = $this->formatDetector->detectFormat($headers);
            $mapping = $this->formatDetector->getColumnMapping($format);

            // Initialize counters
            $totalRecords = count($csvData);
            $successfulRecords = 0;
            $failedRecords = 0;
            $duplicateRecords = 0;
            $skippedRecords = 0;
            $errors = [];

            // Update batch with total count
            $batch->update(['total_records' => $totalRecords]);

            // Process each record
            foreach ($csvData as $index => $record) {
                try {
                    $result = $this->processRecord($record, $format, $mapping, $batch, $index + 1);
                    
                    switch ($result['status']) {
                        case 'success':
                            $successfulRecords++;
                            break;
                        case 'duplicate':
                            $duplicateRecords++;
                            break;
                        case 'skipped':
                            $skippedRecords++;
                            break;
                        case 'failed':
                            $failedRecords++;
                            $errors[] = "Row " . ($index + 1) . ": " . $result['error'];
                            break;
                    }

                    // Update progress every 10 records
                    if (($index + 1) % 10 === 0) {
                        $batch->updateProgress([
                            'processed_records' => $successfulRecords + $failedRecords + $duplicateRecords + $skippedRecords,
                            'successful_records' => $successfulRecords,
                            'failed_records' => $failedRecords,
                            'duplicate_records' => $duplicateRecords,
                            'skipped_records' => $skippedRecords
                        ]);
                    }

                } catch (Exception $e) {
                    $failedRecords++;
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                    
                    Log::error('CSV Import Error', [
                        'batch_id' => $batch->batch_id,
                        'row' => $index + 1,
                        'error' => $e->getMessage(),
                        'record' => $record
                    ]);
                }
            }

            // Prepare results
            $summary = [
                'format_detected' => $this->formatDetector->getFormatDisplayName($format),
                'confidence_score' => $this->formatDetector->getDetectionConfidence($headers, $format),
                'currency_detected' => $this->detectPrimaryCurrency($csvData, $format, $mapping),
                'date_range' => $this->getDateRange($csvData, $format, $mapping)
            ];

            return ImportResult::success(
                totalRecords: $totalRecords,
                successfulRecords: $successfulRecords,
                failedRecords: $failedRecords,
                duplicateRecords: $duplicateRecords,
                skippedRecords: $skippedRecords,
                summary: $summary,
                metadata: [
                    'format' => $format,
                    'mapping' => $mapping,
                    'errors' => array_slice($errors, 0, 20) // Limit errors to first 20
                ]
            );

        } catch (Exception $e) {
            Log::error('CSV Import Failed', [
                'batch_id' => $batch->batch_id,
                'error' => $e->getMessage()
            ]);

            return ImportResult::failure(
                errorMessage: 'Import failed: ' . $e->getMessage(),
                metadata: ['format' => $format ?? 'unknown']
            );
        }
    }

    /**
     * Detect the source type from CSV data
     */
    public function detectSource($data): ?string
    {
        try {
            $csvData = $this->parseCsvData($data);
            if (empty($csvData)) {
                return null;
            }

            $headers = array_keys($csvData[0]);
            $format = $this->formatDetector->detectFormat($headers);

            return match($format) {
                BankFormatDetector::FORMAT_MERCURY => 'mercury',
                BankFormatDetector::FORMAT_PAYONEER => 'payoneer',
                BankFormatDetector::FORMAT_STRIPE_BALANCE => 'stripe',
                BankFormatDetector::FORMAT_STRIPE_PAYMENTS => 'stripe',
                default => null
            };
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get supported file extensions
     */
    public function getSupportedExtensions(): array
    {
        return ['csv', 'txt'];
    }

    /**
     * Get strategy name
     */
    public function getName(): string
    {
        return 'csv';
    }

    /**
     * Check if this strategy can handle the data
     */
    public function canHandle($data): bool
    {
        try {
            $csvData = $this->parseCsvData($data);
            if (empty($csvData)) {
                return false;
            }

            $headers = array_keys($csvData[0]);
            $format = $this->formatDetector->detectFormat($headers);
            
            return $format !== BankFormatDetector::FORMAT_UNKNOWN;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Parse CSV data from various input formats
     */
    private function parseCsvData($data): array
    {
        // Handle string data (CSV content)
        if (is_string($data)) {
            return $this->parseCsvString($data);
        }

        // Handle array data (already parsed)
        if (is_array($data)) {
            return $data;
        }

        throw new Exception('Unsupported data format for CSV parsing');
    }

    /**
     * Parse CSV from string content
     */
    private function parseCsvString(string $csvContent): array
    {
        $lines = str_getcsv($csvContent, "\n", '"', "\\");
        if (empty($lines)) {
            return [];
        }

        $headers = str_getcsv($lines[0], ',', '"', "\\");
        $data = [];

        for ($i = 1; $i < count($lines); $i++) {
            if (trim($lines[$i]) === '') {
                continue; // Skip empty lines
            }

            $row = str_getcsv($lines[$i], ',', '"', "\\");
            
            // Ensure row has same number of columns as headers
            while (count($row) < count($headers)) {
                $row[] = '';
            }

            $data[] = array_combine($headers, array_slice($row, 0, count($headers)));
        }

        return $data;
    }

    /**
     * Process individual record
     */
    private function processRecord(array $record, string $format, array $mapping, ImportBatch $batch, int $rowNumber): array
    {
        try {
            // Extract and parse data
            $transactionData = $this->extractTransactionData($record, $format, $mapping);
            
            // Get or create a default store for the company (temporary for Phase 3)
            $store = \App\Models\Store::where('company_id', $batch->company_id)->first();
            if (!$store) {
                $store = \App\Models\Store::create([
                    'company_id' => $batch->company_id,
                    'name' => 'Default Store',
                    'shopify_domain' => 'default.myshopify.com',
                    'is_active' => true,
                    'sync_enabled' => false
                ]);
            }

            // Create transaction
            $transaction = Transaction::create([
                'store_id' => $store->id, // Required field
                'import_batch_id' => $batch->id,
                'created_by' => $batch->initiated_by,
                'transaction_date' => $transactionData['transaction_date'],
                'amount' => $transactionData['amount'],
                'currency' => $transactionData['currency'] ?? 'USD',
                'description' => $transactionData['description'] ?? '',
                'external_id' => $transactionData['external_id'] ?? null,
                'status' => 'completed',
                'source' => 'bank', // Use valid enum value
                'category' => 'other', // Use valid enum value - will be categorized later
                'type' => $transactionData['amount'] >= 0 ? 'income' : 'expense', // Determine type from amount
                'metadata' => [
                    'import_source' => 'csv',
                    'format' => $format,
                    'row_number' => $rowNumber
                ]
            ]);

            return ['status' => 'success', 'transaction_id' => $transaction->id];

        } catch (Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract transaction data from CSV record
     */
    private function extractTransactionData(array $record, string $format, array $mapping): array
    {
        $data = [];

        // Extract date
        if (isset($mapping['date'])) {
            $data['transaction_date'] = $this->dateParser->parseDate(
                $record[$mapping['date']], 
                $format
            );
        }

        // Extract amount
        if (isset($mapping['amount'])) {
            $currency = isset($mapping['currency']) ? $record[$mapping['currency']] ?? null : null;
            $data['amount'] = $this->amountParser->parseAmount(
                $record[$mapping['amount']], 
                $format, 
                $currency
            );
        }

        // Extract other fields
        $fieldMap = [
            'description' => 'description',
            'currency' => 'currency',
            'id' => 'external_id'
        ];

        foreach ($fieldMap as $csvField => $dataField) {
            if (isset($mapping[$csvField]) && isset($record[$mapping[$csvField]])) {
                $data[$dataField] = $record[$mapping[$csvField]];
            }
        }

        return $data;
    }

    /**
     * Detect primary currency from CSV data
     */
    private function detectPrimaryCurrency(array $csvData, string $format, array $mapping): ?string
    {
        if (!isset($mapping['currency'])) {
            return 'USD'; // Default
        }

        $currencies = [];
        foreach (array_slice($csvData, 0, 10) as $record) {
            if (isset($record[$mapping['currency']])) {
                $currency = $record[$mapping['currency']];
                $currencies[$currency] = ($currencies[$currency] ?? 0) + 1;
            }
        }

        return !empty($currencies) ? array_key_first($currencies) : 'USD';
    }

    /**
     * Get date range from CSV data
     */
    private function getDateRange(array $csvData, string $format, array $mapping): array
    {
        if (!isset($mapping['date'])) {
            return [];
        }

        $dates = [];
        foreach ($csvData as $record) {
            try {
                $date = $this->dateParser->parseDate($record[$mapping['date']], $format);
                $dates[] = $date;
            } catch (Exception $e) {
                // Skip invalid dates
            }
        }

        if (empty($dates)) {
            return [];
        }

        $minDate = min($dates);
        $maxDate = max($dates);

        return [
            'start_date' => $minDate->format('Y-m-d'),
            'end_date' => $maxDate->format('Y-m-d'),
            'days_covered' => $maxDate->diffInDays($minDate) + 1
        ];
    }
} 