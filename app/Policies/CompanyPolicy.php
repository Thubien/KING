<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Policies\Traits\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only super admins can view all companies
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        // Users can only view their own company
        return $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only super admins can create companies
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        // Company owners can update their own company
        if ($user->isCompanyOwner() && $user->company_id === $company->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only super admins can delete companies
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Company $company): bool
    {
        return $this->delete($user, $company);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        return $this->delete($user, $company);
    }

    /**
     * Determine whether the user can manage company settings.
     */
    public function manageSettings(User $user, Company $company): bool
    {
        return $user->isCompanyOwner() && $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can invite users to the company.
     */
    public function inviteUsers(User $user, Company $company): bool
    {
        return ($user->isCompanyOwner() || $user->isAdmin()) && $user->company_id === $company->id;
    }
}