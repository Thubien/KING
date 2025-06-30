<?php

namespace App\Policies\Traits;

use App\Models\User;

trait HandlesAuthorization
{
    /**
     * Check if user is super admin - they can do anything
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    /**
     * Check if user belongs to same company as the model
     */
    protected function belongsToSameCompany(User $user, $model): bool
    {
        // If model has company_id directly
        if (property_exists($model, 'company_id') || method_exists($model, 'getCompanyIdAttribute')) {
            return $model->company_id === $user->company_id;
        }

        // If model has a store relationship
        if (method_exists($model, 'store')) {
            return $model->store && $model->store->company_id === $user->company_id;
        }

        // If model has a company relationship
        if (method_exists($model, 'company')) {
            return $model->company && $model->company->id === $user->company_id;
        }

        return false;
    }

    /**
     * Check if user is company owner or admin
     */
    protected function isCompanyManager(User $user): bool
    {
        return $user->isCompanyOwner() || $user->isAdmin();
    }
}