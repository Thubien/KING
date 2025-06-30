<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use App\Policies\Traits\HandlesAuthorization;

class TransactionPolicy
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
    public function view(User $user, Transaction $transaction): bool
    {
        // Load store relationship if not loaded
        if (!$transaction->relationLoaded('store')) {
            $transaction->load('store');
        }

        // Company owners and admins can view all transactions in their company
        if ($this->isCompanyManager($user)) {
            return $transaction->store && $this->belongsToSameCompany($user, $transaction);
        }

        // Partners can only view transactions from stores they have partnerships in
        if ($user->isPartner()) {
            return $user->hasStoreAccess($transaction->store_id);
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
    public function update(User $user, Transaction $transaction): bool
    {
        // Load store relationship if not loaded
        if (!$transaction->relationLoaded('store')) {
            $transaction->load('store');
        }

        // Only company owners and admins can update transactions
        if ($this->isCompanyManager($user)) {
            return $transaction->store && $this->belongsToSameCompany($user, $transaction);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        // Load store relationship if not loaded
        if (!$transaction->relationLoaded('store')) {
            $transaction->load('store');
        }

        // Only company owners and admins can delete transactions
        if ($this->isCompanyManager($user)) {
            return $transaction->store && $this->belongsToSameCompany($user, $transaction);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        return $this->delete($user, $transaction);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return $this->delete($user, $transaction);
    }
}
