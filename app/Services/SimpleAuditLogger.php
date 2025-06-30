<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SimpleAuditLogger
{
    /**
     * Log critical business actions for audit trail
     */
    public static function log(string $action, string $description, array $data = []): void
    {
        $user = auth()->user();
        
        $logData = [
            'action' => $action,
            'description' => $description,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'company_id' => $user?->company_id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
            'data' => $data,
        ];

        // Log to dedicated audit channel
        Log::channel('audit')->info("AUDIT: {$action}", $logData);
    }

    /**
     * Log partner management actions
     */
    public static function logPartnerAction(string $action, $partnership, array $extra = []): void
    {
        static::log(
            "PARTNER_{$action}",
            "Partnership {$action}: {$partnership->user->name} in {$partnership->store->name}",
            array_merge([
                'partnership_id' => $partnership->id,
                'partner_name' => $partnership->user->name,
                'store_name' => $partnership->store->name,
                'ownership_percentage' => $partnership->ownership_percentage,
            ], $extra)
        );
    }

    /**
     * Log financial actions (large transactions)
     */
    public static function logFinancialAction(string $action, $transaction, array $extra = []): void
    {
        // Only log large amounts or critical categories
        if ($transaction->amount_usd >= 1000 || in_array($transaction->category, ['WITHDRAW', 'PARTNER_REPAYMENT'])) {
            static::log(
                "FINANCIAL_{$action}",
                "Financial {$action}: {$transaction->category} of {$transaction->amount} {$transaction->currency}",
                array_merge([
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->amount,
                    'amount_usd' => $transaction->amount_usd,
                    'currency' => $transaction->currency,
                    'category' => $transaction->category,
                    'store_id' => $transaction->store_id,
                ], $extra)
            );
        }
    }

    /**
     * Log company/store management actions
     */
    public static function logCompanyAction(string $action, $model, array $extra = []): void
    {
        $modelType = class_basename($model);
        
        static::log(
            "COMPANY_{$action}",
            "{$modelType} {$action}: {$model->name}",
            array_merge([
                'model_type' => $modelType,
                'model_id' => $model->id,
                'model_name' => $model->name,
            ], $extra)
        );
    }

    /**
     * Log import/export actions
     */
    public static function logImportAction(string $action, $importBatch, array $extra = []): void
    {
        static::log(
            "IMPORT_{$action}",
            "Import {$action}: {$importBatch->filename}",
            array_merge([
                'import_batch_id' => $importBatch->id,
                'filename' => $importBatch->filename,
                'detected_format' => $importBatch->detected_format,
                'total_rows' => $importBatch->total_rows,
                'processed_rows' => $importBatch->processed_rows,
            ], $extra)
        );
    }

    /**
     * Log authentication events
     */
    public static function logAuthAction(string $action, $user = null, array $extra = []): void
    {
        $user = $user ?? auth()->user();
        
        static::log(
            "AUTH_{$action}",
            "Authentication {$action}: {$user?->email}",
            array_merge([
                'user_email' => $user?->email,
                'user_name' => $user?->name,
                'login_time' => now(),
            ], $extra)
        );
    }
}