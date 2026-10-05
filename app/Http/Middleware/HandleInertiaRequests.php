<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** Storefront pages use their own HTML template (theme colours, fonts, SEO tags); admin/vendor/driver use app. */
    public function rootView(Request $request): string
    {
        return \App\Support\Storefront::isStorefrontRequest($request) ? 'storefront' : 'app';
    }

    public function handle(Request $request, \Closure $next)
    {
        // Server-side rendering is only for storefront pages (search engines, first paint); the back-office apps render in the browser.
        if (!\App\Support\Storefront::isStorefrontRequest($request)) {
            config(['inertia.ssr.enabled' => false]);
        }

        return $this->leaveAppWhenNeeded($request, parent::handle($request, $next));
    }

    /**
     * A React storefront visit can end somewhere the storefront app cannot show: a redirect to the admin/vendor/driver
     * apps or a payment site, or a page still rendered with Blade. Those get a full page load instead of an error.
     */
    protected function leaveAppWhenNeeded(Request $request, $response)
    {
        if (!$request->header('X-Inertia') || !\App\Support\Storefront::isStorefrontRequest($request)) {
            return $response;
        }

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $target = $response->getTargetUrl();
            $sameSite = parse_url($target, PHP_URL_HOST) === $request->getHost();
            if (!$sameSite || !\App\Support\Storefront::isStorefrontRequest(Request::create($target))) {
                return \Inertia\Inertia::location($target);
            }

            return $response;
        }

        if ($request->isMethod('GET') && !$response->headers->has('X-Inertia') && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return \Inertia\Inertia::location($request->fullUrl());
        }

        return $response;
    }

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        if (\App\Support\Storefront::isStorefrontRequest($request)) {
            return array_merge(parent::share($request), [
                'sf' => fn () => \App\Support\Storefront::shared($request),
                // Login/logout issue a new token; the layout copies it into the csrf-token meta tag.
                'csrf' => fn () => csrf_token(),
                'flash' => [
                    'success' => fn () => $request->session()->get('success'),
                    'error' => fn () => $request->session()->get('error'),
                    'warning' => fn () => $request->session()->get('warning'),
                    'info' => fn () => $request->session()->get('info'),
                ],
            ]);
        }

        return array_merge(parent::share($request), [
            'app' => [
                'name' => setting('store.name', 'LaravelShop'),
                'logo' => ($logo = setting('store.admin_logo') ?: setting('store.logo')) ? \Storage::url($logo) : null,
                'hideName' => (bool) setting('store.hide_name_in_header'),
                'defaultTheme' => setting('theme.admin_default', 'light'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'admin_role' => $user->admin_role,
                ] : null,
            ],
            'features' => [
                'blog' => (bool) setting('features.blog_enabled'),
                'multiVendor' => (bool) setting('features.multi_vendor'),
                'loyalty' => (bool) setting('features.loyalty_points'),
                'driverPortal' => (bool) setting('features.driver_portal'),
                'localization' => (bool) setting('features.localization_enabled', true),
            ],
            'modules' => collect(\App\Support\Modules::all())->map(fn ($m, $k) => \App\Support\Modules::enabled($k)),
            // vendor portal: store name / status / pending orders for the sidebar (only for vendor users)
            'vendor' => fn () => ($v = $user?->vendor) ? [
                'id' => $v->id, 'store_name' => $v->store_name, 'slug' => $v->slug, 'status' => $v->status,
                'pending_orders' => $v->status === 'approved'
                    ? \App\Models\Order::where('status', 'pending')->whereHas('items.product', fn ($q) => $q->where('vendor_id', $v->id))->count()
                    : 0,
            ] : null,
            // driver portal: availability and unread notifications for the header (only on /driver pages)
            'driver' => fn () => ($user?->is_driver && $request->is('driver', 'driver/*')) ? [
                'status' => $user->driver_status ?? 'offline',
                'unread' => \App\Models\DriverNotification::where('driver_id', $user->id)->where('is_read', false)->count(),
                'active_orders' => \App\Models\Order::where('driver_id', $user->id)->whereIn('status', ['processing', 'shipped'])->count(),
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ]);
    }
}
