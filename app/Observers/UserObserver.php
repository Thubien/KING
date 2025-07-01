<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Company;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // If user doesn't have a company, create one
        if (!$user->company_id) {
            $company = Company::create([
                'name' => $user->name . "'s Company",
                'status' => 'active',
                'is_trial' => true,
                'trial_ends_at' => now()->addDays(14),
                'subscription_plan' => 'trial',
            ]);

            // Update user with company
            $user->update(['company_id' => $company->id]);

            // Assign owner role
            $user->assignRole('owner');
        }
    }
}