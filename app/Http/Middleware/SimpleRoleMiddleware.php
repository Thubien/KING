<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SimpleRoleMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Usage: Route::middleware(['auth', 'role:owner,partner'])
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Super admin bypasses all role checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if ($this->hasRole($user, $role)) {
                return $next($request);
            }
        }

        // User doesn't have required role
        abort(403, 'Bu sayfaya erişim yetkiniz yok.');
    }

    /**
     * Check if user has specific role
     */
    private function hasRole($user, string $role): bool
    {
        return match($role) {
            'owner' => $user->isOwner(),
            'partner' => $user->isPartner(),
            'staff' => $user->isStaff(),
            'super_admin' => $user->isSuperAdmin(),
            default => false,
        };
    }
}