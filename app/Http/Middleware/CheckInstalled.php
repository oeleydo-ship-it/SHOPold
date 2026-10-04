<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    /**
     * Handle an incoming request.
     * Redirect to installer if app is not installed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for install routes
        if ($request->is('install*')) {
            return $next($request);
        }

        // Check if installed
        if (!File::exists(storage_path('installed'))) {
            return redirect()->route('install.welcome');
        }

        return $next($request);
    }
}
