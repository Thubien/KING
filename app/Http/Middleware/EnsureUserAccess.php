<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Ensure user is authenticated
        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Ensure user has valid company_id
        if (!$user->company_id) {
            abort(403, 'User must be associated with a company.');
        }

        // Ensure user is active
        if (!$user->is_active) {
            abort(403, 'User account is inactive.');
        }

        // Set company context for global scopes
        if (!session()->has('company_id')) {
            session(['company_id' => $user->company_id]);
        }

        // Update last login timestamp
        if (!$user->last_login_at || $user->last_login_at->diffInMinutes(now()) > 30) {
            $user->update(['last_login_at' => now()]);
        }

        // Redirect partners to partner dashboard if they're trying to access admin routes
        if ($user->user_type === 'partner' && $request->is('admin/*') && !$request->is('admin/partner-dashboard')) {
            // Allow access to partner-specific resources
            $allowedPartnerRoutes = [
                'admin/partner-dashboard',
                'admin/partnerships',
                'admin/transactions',
                'admin/stores',
            ];
            
            $currentPath = $request->path();
            $isAllowedRoute = collect($allowedPartnerRoutes)->contains(function ($route) use ($currentPath) {
                return str_starts_with($currentPath, $route);
            });
            
            if (!$isAllowedRoute) {
                return redirect()->route('filament.admin.pages.partner-dashboard');
            }
        }

        return $next($request);
    }
}