<?php

namespace App\Policies;

use App\Models\Partnership;
use App\Models\User;
use App\Policies\Traits\HandlesAuthorization;

class PartnershipPolicy
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
    public function view(User $user, Partnership $partnership): bool
    {
        // Company owners and admins can view all partnerships in their company
        if ($this->isCompanyManager($user)) {
            return $partnership->store && $this->belongsToSameCompany($user, $partnership);
        }

        // Partners can only view their own partnerships
        if ($user->isPartner()) {
            return $partnership->user_id === $user->id;
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
    public function update(User $user, Partnership $partnership): bool
    {
        // Only company owners and admins can update partnerships
        if ($this->isCompanyManager($user)) {
            return $partnership->store && $this->belongsToSameCompany($user, $partnership);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Partnership $partnership): bool
    {
        // Only company owners and admins can delete partnerships
        if ($this->isCompanyManager($user)) {
            return $partnership->store && $this->belongsToSameCompany($user, $partnership);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Partnership $partnership): bool
    {
        return $this->delete($user, $partnership);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Partnership $partnership): bool
    {
        return $this->delete($user, $partnership);
    }

    /**
     * Determine whether the user can send invitations.
     */
    public function sendInvitation(User $user, Partnership $partnership): bool
    {
        return $this->update($user, $partnership);
    }

    /**
     * Determine whether the user can resend invitations.
     */
    public function resendInvitation(User $user, Partnership $partnership): bool
    {
        return $this->update($user, $partnership);
    }
}
