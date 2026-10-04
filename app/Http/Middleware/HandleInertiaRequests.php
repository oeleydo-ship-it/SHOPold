<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

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
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ]);
    }
}
