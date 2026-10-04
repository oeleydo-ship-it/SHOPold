<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if driver feature is enabled (default to true)
        if (!setting('features.driver_portal', true)) {
            abort(404);
        }

        // Check if user is authenticated and is a driver
        if (!auth()->check() || !auth()->user()->is_driver) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            return redirect()->route('login')->with('error', 'You must be a driver to access this area.');
        }

        return $next($request);
    }
}
