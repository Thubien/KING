<?php

namespace App\Services\Import\Contracts;

use App\Models\Store;
use App\Services\Import\ImportResult;

interface ApiImportStrategyInterface
{
    /**
     * Import data from external API
     */
    public function import(array $credentials, Store $store): ImportResult;

    /**
     * Validate the API credentials
     */
    public function validate(array $credentials): array;

    /**
     * Get the strategy name/identifier
     */
    public function getName(): string;

    /**
     * Get the strategy description
     */
    public function getDescription(): string;

    /**
     * Get required credential fields
     */
    public function getRequiredCredentials(): array;

    /**
     * Check if this is a premium feature
     */
    public function isPremiumFeature(): bool;
}
