<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Allowed roles (super_admin, manager, editor)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            abort(403, 'Unauthorized access.');
        }

        // If no specific roles are required, just check if user is admin
        if (empty($roles)) {
            return $next($request);
        }

        // Super admin always has access
        if ($user->admin_role === 'super_admin') {
            return $next($request);
        }

        // Check if user has one of the required roles
        if (in_array($user->admin_role, $roles)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this resource.');
    }
}
