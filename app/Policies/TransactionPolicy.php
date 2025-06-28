<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
{
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
        // Company owners and admins can view all transactions in their company
        if ($user->isCompanyOwner() || $user->isAdmin()) {
            return $transaction->store->company_id === $user->company_id;
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
        return $user->isCompanyOwner() || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        // Only company owners and admins can update transactions
        if ($user->isCompanyOwner() || $user->isAdmin()) {
            return $transaction->store->company_id === $user->company_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        // Only company owners and admins can delete transactions
        if ($user->isCompanyOwner() || $user->isAdmin()) {
            return $transaction->store->company_id === $user->company_id;
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