<?php

namespace App\Http\Controllers;

use App\Models\Partnership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class PartnerInvitationController extends Controller
{
    /**
     * Show the partner invitation acceptance form.
     */
    public function showAcceptForm(string $token)
    {
        $partnership = Partnership::where('invitation_token', $token)
            ->where('status', 'PENDING_INVITATION')
            ->whereNotNull('invited_at')
            ->where('invited_at', '>', now()->subDays(7)) // Check if not expired
            ->first();

        if (!$partnership) {
            return view('partnership.invalid-invitation')->with([
                'message' => 'This invitation is invalid or has expired.'
            ]);
        }

        return view('partnership.accept-invitation', [
            'partnership' => $partnership,
            'token' => $token,
            'partnerEmail' => $partnership->partner_email,
            'storeName' => $partnership->store->name,
            'ownershipPercentage' => $partnership->ownership_percentage,
        ]);
    }

    /**
     * Process the partner invitation acceptance.
     */
    public function acceptInvitation(Request $request, string $token)
    {
        $partnership = Partnership::where('invitation_token', $token)
            ->where('status', 'PENDING_INVITATION')
            ->whereNotNull('invited_at')
            ->where('invited_at', '>', now()->subDays(7))
            ->first();

        if (!$partnership) {
            return redirect()->route('partnership.invalid')
                ->with('error', 'This invitation is invalid or has expired.');
        }

        // Validate the form data
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check if user already exists with this email
        $existingUser = User::where('email', $partnership->partner_email)->first();
        
        if ($existingUser) {
            return redirect()->back()
                ->with('error', 'A user with this email already exists. Please contact the administrator.')
                ->withInput();
        }

        // Create the new partner user
        $user = User::create([
            'name' => $request->name,
            'email' => $partnership->partner_email,
            'password' => Hash::make($request->password),
            'company_id' => $partnership->store->company_id,
            'user_type' => 'partner',
            'is_active' => true,
        ]);

        // Assign partner role
        $user->assignRoleBasedOnUserType();

        // Update partnership
        $partnership->update([
            'user_id' => $user->id,
            'status' => 'ACTIVE',
            'activated_at' => now(),
            'invitation_token' => null, // Clear token for security
        ]);

        // Log the user in
        auth()->login($user);

        return redirect()->route('filament.admin.pages.partner-dashboard')
            ->with('success', 'Welcome! Your partnership has been activated successfully.');
    }

    /**
     * Show invalid invitation page.
     */
    public function invalidInvitation()
    {
        return view('partnership.invalid-invitation', [
            'message' => 'This invitation link is invalid or has expired.'
        ]);
    }
}
