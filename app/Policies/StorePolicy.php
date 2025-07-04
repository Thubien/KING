<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use App\Policies\Traits\HandlesAuthorization;

class StorePolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isCompanyOwner() || $user->isAdmin() || $user->isPartner();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Store $store): bool
    {
        // Company owners and admins can view all stores in their company
        if ($this->isCompanyManager($user)) {
            return $this->belongsToSameCompany($user, $store);
        }

        // Partners can only view stores they have partnerships in
        if ($user->isPartner()) {
            return $user->hasStoreAccess($store->id);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isCompanyManager($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Store $store): bool
    {
        // Only company owners and admins can update stores
        if ($this->isCompanyManager($user)) {
            return $this->belongsToSameCompany($user, $store);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Store $store): bool
    {
        // Only company owners and admins can delete stores
        if ($this->isCompanyManager($user)) {
            return $this->belongsToSameCompany($user, $store);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Store $store): bool
    {
        return $this->delete($user, $store);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Store $store): bool
    {
        return $this->delete($user, $store);
    }
}
