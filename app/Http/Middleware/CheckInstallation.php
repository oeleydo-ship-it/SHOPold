<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip check for install routes
        if ($request->is('install*')) {
            return $next($request);
        }

        // Check if app is installed
        if (!$this->isInstalled()) {
            return redirect('/install');
        }

        return $next($request);
    }

    /**
     * Check if the application is installed
     */
    private function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }
}