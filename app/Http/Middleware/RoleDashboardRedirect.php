<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleDashboardRedirect
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Only redirect on dashboard access
        if (!$user) {
            return $next($request);
        }

        // Check if we're on the dashboard route
        try {
            if (!$request->routeIs('filament.admin.pages.dashboard')) {
                return $next($request);
            }
        } catch (\Exception $e) {
            // Route checking failed, just continue
            return $next($request);
        }

        // ROLE-BASED DASHBOARD REDIRECTS
        
        // STAFF -> Manual Orders (sipariş giriş sayfası)
        if ($user->isStaff()) {
            return redirect()->route('filament.admin.resources.manual-orders.index');
        }

        // PARTNER -> Partner Dashboard (karlılık görünümü)  
        if ($user->isPartner()) {
            return redirect()->route('filament.admin.pages.partner-dashboard');
        }

        // OWNER -> Company Overview (genel bakış)
        if ($user->isOwner()) {
            // Default dashboard is perfect for owners
            return $next($request);
        }

        // SUPER ADMIN -> Default dashboard
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        return $next($request);
    }
}