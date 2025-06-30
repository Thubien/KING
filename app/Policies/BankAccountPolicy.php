<?php

namespace App\Policies;

use App\Models\BankAccount;
use App\Models\User;
use App\Policies\Traits\HandlesAuthorization;

class BankAccountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_bank_accounts') || $user->can('manage_bank_accounts');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BankAccount $bankAccount): bool
    {
        // Company owners and admins can view all bank accounts in their company
        if ($this->isCompanyManager($user)) {
            return $this->belongsToSameCompany($user, $bankAccount);
        }

        // Partners can view bank accounts if they have permission
        if ($user->isPartner() && $user->can('view_bank_accounts')) {
            return $this->belongsToSameCompany($user, $bankAccount);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_bank_accounts');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BankAccount $bankAccount): bool
    {
        // Only users with manage permission can update
        if ($user->can('manage_bank_accounts') || $user->can('edit_bank_accounts')) {
            return $this->belongsToSameCompany($user, $bankAccount);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BankAccount $bankAccount): bool
    {
        // Only users with manage permission can delete
        if ($user->can('manage_bank_accounts')) {
            return $this->belongsToSameCompany($user, $bankAccount);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BankAccount $bankAccount): bool
    {
        return $this->delete($user, $bankAccount);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BankAccount $bankAccount): bool
    {
        return $this->delete($user, $bankAccount);
    }
}