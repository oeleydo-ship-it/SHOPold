<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\ThemeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the active theme to storefront requests: its customizer values override the store-wide theme.*
 * settings and its views take precedence over the shared ones. Runs after the session so an admin's
 * theme preview is visible. The admin area is never themed.
 */
class ApplyTheme
{
    public function __construct(protected ThemeService $themes)
    {
    }

    /** Settings the storefront already checks, forced off while the owning module is off. */
    protected function moduleOverlay(): array
    {
        $off = [];
        if (!\App\Support\Modules::enabled('wishlist')) {
            $off += ['products.show_wishlist' => false, 'theme.header_show_wishlist' => false];
        }
        if (!\App\Support\Modules::enabled('compare')) {
            $off += ['products.show_compare' => false, 'theme.header_show_compare' => false];
        }

        return $off;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->is('admin', 'admin/*', 'install', 'install/*', 'up')) {
            try {
                Setting::overlay(array_merge($this->themes->overlay(), $this->moduleOverlay()));
                $this->themes->registerViewPath();
            } catch (\Throwable $e) {
                report($e); // never take the storefront down because of a theme problem
            }
        } else {
            Setting::overlay([]); // the admin area always sees the store-wide settings
        }

        return $next($request);
    }
}
