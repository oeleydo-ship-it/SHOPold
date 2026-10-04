<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if multi-vendor feature is enabled
        if (!setting('features.multi_vendor', false)) {
            abort(404, 'Vendor feature is not enabled.');
        }

        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Allow access to registration
        if ($request->routeIs('vendor.register') || $request->routeIs('vendor.register.store')) {
            return $next($request);
        }

        // Check if user is a vendor
        if (!$user->vendor) {
            return redirect()->route('vendor.register');
        }

        // Check if vendor is approved (for routes other than dashboard)
        if (!$user->vendor->isApproved() && !$request->routeIs('vendor.dashboard')) {
            return redirect()->route('vendor.dashboard')
                ->with('warning', 'Your vendor account is pending approval.');
        }

        return $next($request);
    }
}

