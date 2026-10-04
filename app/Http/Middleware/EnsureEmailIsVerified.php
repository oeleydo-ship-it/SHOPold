<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if email verification is enabled
        if (!setting('system.require_email_verification', false)) {
            return $next($request);
        }

        // Check if user is authenticated and email is not verified
        if ($request->user() && !$request->user()->hasVerifiedEmail()) {
            // Allow access to verification routes
            if ($request->routeIs('verification.*') || $request->routeIs('logout')) {
                return $next($request);
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
