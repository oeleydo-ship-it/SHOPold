<?php

namespace App\Support;

use App\Models\Category;
use App\Services\ThemeService;
use App\View\StoreFooter;
use App\View\StoreSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * The React (Inertia) storefront: which requests belong to it and the data every storefront page shares.
 * Controllers render pages with Inertia::render('Storefront/...'); the HTML shell is views/storefront.blade.php.
 */
class Storefront
{
    /** Paths that are not storefront pages (they have their own Inertia apps or none). */
    protected const NOT_STOREFRONT = ['admin', 'admin/*', 'vendor', 'vendor/*', 'driver', 'driver/*', 'install', 'install/*', 'api/*', 'up'];

    public static function isStorefrontRequest(Request $request): bool
    {
        return !$request->is(...self::NOT_STOREFRONT);
    }

    /**
     * Props every storefront page receives (shared through HandleInertiaRequests). Built lazily, after the
     * theme middleware has applied the active theme's options.
     */
    public static function shared(Request $request): array
    {
        $s = StoreSettings::data();
        $user = $request->user();
        $themes = app(ThemeService::class);

        return [
            'store' => [
                'name' => (string) $s['store_name'],
                'tagline' => (string) $s['store_tagline'],
                'email' => (string) $s['store_email'],
                'phone' => (string) $s['store_phone'],
                'address' => (string) $s['store_address'],
                'logo' => $s['store_logo'] ? Storage::url($s['store_logo']) : null,
                'favicon' => $s['store_favicon'] ? Storage::url($s['store_favicon']) : null,
                'hideName' => (bool) $s['hide_name_in_header'],
                'allowRegistration' => (bool) setting('system.allow_registration', true),
            ],
            'locale' => [
                'current' => (string) $s['current_locale'],
                'rtl' => (bool) $s['is_rtl'],
                'languages' => $s['available_languages'],
                'enabled' => (bool) ($s['features']['localization_enabled'] ?? true),
            ],
            'currency' => [
                'current' => (string) $s['currency'],
                'symbol' => (string) $s['currency_symbol'],
                'enabled' => array_map(fn ($c) => ['code' => $c, 'symbol' => currency_symbol($c)], $s['enabled_currencies']),
                'multi' => (bool) ($s['features']['multi_currency'] ?? false),
            ],
            'theme' => [
                'slug' => $themes->getActive(),
                'options' => $s['theme'],
                'heading_font' => (string) setting('theme.heading_font', ''),
                'mobile' => [
                    'showPromo' => (bool) setting('products.mobile_show_promo', true),
                    'showSearch' => (bool) setting('products.mobile_show_search', true),
                    'showLanguage' => (bool) setting('products.mobile_show_language', true),
                ],
            ],
            'nav' => array_map(fn ($item) => [
                'label' => (string) ($item['label'] ?? ''),
                'url' => (string) ($item['url'] ?? '#'),
                'route' => $item['route'] ?? null,
                'active' => !empty($item['route']) && $request->routeIs($item['route'] . '*'),
            ], is_array($s['navigation']['header']) ? $s['navigation']['header'] : []),
            'footer' => StoreFooter::data($s),
            'social' => array_filter($s['social'], fn ($url) => is_string($url) && preg_match('#^https?://#i', trim($url))),
            'features' => [
                'wishlist' => Modules::enabled('wishlist'),
                'compare' => Modules::enabled('compare'),
                'newsletter' => Modules::enabled('newsletter'),
                'reviews' => (bool) ($s['features']['reviews_enabled'] ?? true),
                'multiVendor' => (bool) setting('features.multi_vendor'),
                'driverPortal' => (bool) setting('features.driver_portal', true),
                'blog' => Modules::enabled('blog'),
            ],
            'categories' => static::menuCategories(),
            'cart' => ['count' => (int) \App\Models\Cart::getCart()->item_count],
            'customer' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
                'isAdmin' => (bool) $user->is_admin,
                'isDriver' => (bool) $user->is_driver,
                'hasStore' => (bool) $user->vendor,
            ] : null,
            'seo' => [
                'title' => (string) ($s['seo']['meta_title'] ?: $s['store_name']),
                'description' => (string) $s['seo']['meta_description'],
                'keywords' => (string) $s['seo']['meta_keywords'],
            ],
            'card' => static::cardSettings(),
            'search' => (string) $request->query('search', ''),
            'lang' => static::messages(),
        ];
    }

    /** Active top-level categories for the header menus (mega menu, mobile menu). Cached for 5 minutes. */
    public static function menuCategories(): array
    {
        $locale = app()->getLocale();

        return Cache::remember("storefront.menu-categories.{$locale}", 300, fn () => Category::where('is_active', true)
            ->with('translations')->orderBy('name')->take(12)->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->translated_name ?? $c->name,
                'slug' => $c->slug,
                'image' => $c->image ? asset('storage/' . $c->image) : null,
            ])->values()->all());
    }

    /** Product card display settings (Settings > Products > mobile / desktop card). */
    public static function cardSettings(): array
    {
        return [
            'mobileTitlePx' => (int) setting('products.mobile_title_size_px', 13),
            'mobilePricePx' => (int) setting('products.mobile_price_px', 16),
            'mobileCategoryPx' => (int) setting('products.mobile_category_px', 10),
            'mobileTitleLines' => (int) setting('products.mobile_title_lines', 2),
            'mobilePaddingPx' => (int) setting('products.mobile_card_padding_px', 12),
            'mobileHideRating' => (bool) setting('products.mobile_hide_rating', false),
            'mobileHideCategory' => (bool) setting('products.mobile_hide_category', false),
            'mobileHideStock' => (bool) setting('products.mobile_hide_stock', false),
            'mobileShowCartBtn' => (bool) setting('products.mobile_show_cart_btn', true),
            'mobileImageRatio' => (string) setting('products.mobile_image_ratio', 'square'),
            'desktopTitlePx' => (int) setting('products.desktop_card_title_px', 16),
            'desktopPricePx' => (int) setting('products.desktop_card_price_px', 18),
            'desktopTitleLines' => (int) setting('products.desktop_title_lines', 2),
            'titleCharLimit' => (int) setting('products.title_char_limit', 0),
            'mobileTitleCharLimit' => (int) setting('products.mobile_title_char_limit', 0),
            'mobileColumns' => (string) setting('products.mobile_columns', '2'),
            'mobileGapPx' => (int) setting('products.mobile_card_gap_px', 8),
            'homepageMobileColumns' => (string) setting('products.homepage_mobile_columns', '2'),
            'homepageMobileScroll' => (bool) setting('products.homepage_mobile_scroll', false),
            'homepageMobileCount' => (int) setting('products.homepage_mobile_count', 4),
        ];
    }

    /** The active language's storefront strings (lang/<locale>/messages.php), falling back to English. */
    public static function messages(): array
    {
        $locale = app()->getLocale();

        return Cache::remember("storefront.messages.{$locale}", 3600, function () use ($locale) {
            $load = fn (string $l) => is_file($f = lang_path("{$l}/messages.php")) ? (array) require $f : [];

            return array_merge($load('en'), $load($locale));
        });
    }
}
