<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.force_https') && !$request->secure() && !app()->environment('local')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
} 