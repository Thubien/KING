<?php

namespace App\Policies;

use App\Models\PaymentProcessorAccount;
use App\Models\User;
use App\Policies\Traits\HandlesAuthorization;

class PaymentProcessorAccountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage_payment_processors') || $this->isCompanyManager($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaymentProcessorAccount $paymentProcessor): bool
    {
        // Company owners and admins can view all payment processors in their company
        if ($this->isCompanyManager($user)) {
            return $this->belongsToSameCompany($user, $paymentProcessor);
        }

        // Partners can view if they have store access
        if ($user->isPartner()) {
            return $this->belongsToSameCompany($user, $paymentProcessor);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_payment_processors');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaymentProcessorAccount $paymentProcessor): bool
    {
        // Only users with manage permission can update
        if ($user->can('manage_payment_processors')) {
            return $this->belongsToSameCompany($user, $paymentProcessor);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaymentProcessorAccount $paymentProcessor): bool
    {
        // Only users with manage permission can delete
        if ($user->can('manage_payment_processors')) {
            return $this->belongsToSameCompany($user, $paymentProcessor);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PaymentProcessorAccount $paymentProcessor): bool
    {
        return $this->delete($user, $paymentProcessor);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PaymentProcessorAccount $paymentProcessor): bool
    {
        return $this->delete($user, $paymentProcessor);
    }
}