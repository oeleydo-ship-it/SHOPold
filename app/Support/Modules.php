<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Registry of the store's optional modules. Each module is switched on/off through one setting
 * (so existing code that reads setting('features.xyz') keeps working) and can expose its own options.
 *
 * Option types: toggle | text | number | select. Options are stored under the key given.
 */
class Modules
{
    public static function all(): array
    {
        $on = fn ($key, $label, $default = true) => ['key' => $key, 'label' => $label, 'type' => 'toggle', 'default' => $default];

        return [
            'reviews' => [
                'name' => 'Product reviews', 'group' => 'Sales', 'icon' => 'Star', 'toggle' => 'features.reviews_enabled', 'default' => false,
                'description' => 'Customers can rate and review products.', 'link' => '/admin/reviews',
            ],
            'wishlist' => [
                'name' => 'Wishlist', 'group' => 'Sales', 'icon' => 'Heart', 'toggle' => 'features.wishlist_enabled', 'default' => false,
                'description' => 'Save products for later.',
            ],
            'compare' => [
                'name' => 'Product compare', 'group' => 'Sales', 'icon' => 'GitCompare', 'toggle' => 'features.compare_enabled', 'default' => false,
                'description' => 'Side-by-side product comparison.',
            ],
            'guest_checkout' => [
                'name' => 'Guest checkout', 'group' => 'Sales', 'icon' => 'ShoppingBag', 'toggle' => 'features.guest_checkout', 'default' => false,
                'description' => 'Let customers order without creating an account.',
                'options' => [
                    $on('features.guest_checkout_otp', 'Verify guests by email code', true),
                    $on('features.guest_checkout_phone_otp', 'Verify guests by phone code', false),
                    $on('features.guest_create_account_option', 'Offer to create an account after ordering', true),
                ],
            ],
            'digital_products' => [
                'name' => 'Digital products', 'group' => 'Sales', 'icon' => 'FileDown', 'toggle' => 'features.digital_products', 'default' => false,
                'description' => 'Sell downloadable files.',
                'options' => [$on('features.digital_require_login', 'Require login to download', true)],
            ],
            'multi_currency' => [
                'name' => 'Multi-currency', 'group' => 'Sales', 'icon' => 'Coins', 'toggle' => 'features.multi_currency', 'default' => false,
                'description' => 'Show prices in several currencies.', 'options' => [$on('features.live_currency_converter', 'Live exchange rates', false)],
            ],
            'multi_vendor' => [
                'name' => 'Multi-vendor marketplace', 'group' => 'Sales', 'icon' => 'Store', 'toggle' => 'features.multi_vendor', 'default' => false,
                'description' => 'Let vendors sell on your store and request payouts.', 'link' => '/admin/vendors',
                'options' => [
                    ['key' => 'features.vendor_commission', 'label' => 'Platform commission (%)', 'type' => 'number', 'default' => 10, 'min' => 0, 'max' => 100],
                    $on('features.vendor_products_approval', 'Approve vendor products before they go live', false),
                    $on('features.vendor_shipping', 'Vendors manage their own shipping', true),
                ],
            ],
            'loyalty' => [
                'name' => 'Loyalty points', 'group' => 'Customers', 'icon' => 'Gift', 'toggle' => 'features.loyalty_points', 'default' => false,
                'description' => 'Reward customers with points.', 'link' => '/admin/loyalty',
                'options' => [
                    ['key' => 'features.points_per_dollar', 'label' => 'Points earned per currency unit', 'type' => 'number', 'default' => 1, 'min' => 0, 'max' => 1000],
                    ['key' => 'features.min_points_redeem', 'label' => 'Minimum points to redeem', 'type' => 'number', 'default' => 100, 'min' => 0, 'max' => 100000],
                    ['key' => 'features.points_expiry', 'label' => 'Points expire after (days, 0 = never)', 'type' => 'number', 'default' => 365, 'min' => 0, 'max' => 3650],
                    ['key' => 'features.signup_bonus_points', 'label' => 'Signup bonus points', 'type' => 'number', 'default' => 50, 'min' => 0, 'max' => 100000],
                    ['key' => 'features.referral_bonus_points', 'label' => 'Referral bonus points', 'type' => 'number', 'default' => 100, 'min' => 0, 'max' => 100000],
                ],
            ],
            'newsletter' => [
                'name' => 'Newsletter', 'group' => 'Customers', 'icon' => 'Mail', 'toggle' => 'features.newsletter_enabled', 'default' => false,
                'description' => 'Newsletter signup forms and subscriber list.', 'link' => '/admin/newsletters',
            ],
            'live_chat' => [
                'name' => 'Live chat widget', 'group' => 'Customers', 'icon' => 'Headset', 'toggle' => 'features.live_chat_enabled', 'default' => false,
                'description' => 'Third-party chat widget (Tawk, Crisp, Intercom or custom code). Support tickets stay available.',
            ],
            'blog' => [
                'name' => 'Blog', 'group' => 'Content', 'icon' => 'BookOpen', 'toggle' => 'features.blog_enabled', 'default' => false,
                'description' => 'Publish articles and news.', 'link' => '/admin/blog',
                'options' => [
                    $on('features.blog_comments', 'Allow comments', true),
                    ['key' => 'features.blog_posts_per_page', 'label' => 'Posts per page', 'type' => 'number', 'default' => 10, 'min' => 3, 'max' => 48],
                ],
            ],
            'localization' => [
                'name' => 'Languages & translations', 'group' => 'Content', 'icon' => 'Languages', 'toggle' => 'features.localization_enabled', 'default' => true,
                'description' => 'Translate products, categories and pages.', 'link' => '/admin/translations',
            ],
            'ai_chat' => [
                'name' => 'AI shopping assistant', 'group' => 'Content', 'icon' => 'Sparkles', 'toggle' => 'features.ai_chat_enabled', 'default' => false,
                'description' => 'Chat assistant that helps customers find products.', 'link' => '/admin/ai-conversations',
            ],
            'driver_portal' => [
                'name' => 'Driver portal', 'group' => 'Operations', 'icon' => 'Bike', 'toggle' => 'features.driver_portal', 'default' => false,
                'description' => 'Delivery drivers get their own portal and earnings.', 'link' => '/admin/drivers',
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    public static function enabled(string $key): bool
    {
        $module = static::get($key);

        return $module ? (bool) Setting::get($module['toggle'], $module['default']) : false;
    }

    /** Current option values of a module. */
    public static function optionValues(array $module): array
    {
        $values = [];
        foreach ($module['options'] ?? [] as $opt) {
            $values[$opt['key']] = Setting::getFresh($opt['key'], $opt['default']);
        }

        return $values;
    }

    /** Validate submitted option values against the module's definition. */
    public static function sanitizeOptions(array $module, array $input): array
    {
        $clean = [];
        foreach ($module['options'] ?? [] as $opt) {
            if (!array_key_exists($opt['key'], $input)) {
                continue;
            }
            $v = $input[$opt['key']];
            $clean[$opt['key']] = match ($opt['type']) {
                'toggle' => filter_var($v, FILTER_VALIDATE_BOOLEAN),
                'number' => is_numeric($v) ? max($opt['min'] ?? PHP_INT_MIN, min($opt['max'] ?? PHP_INT_MAX, (int) $v)) : ($opt['default'] ?? 0),
                default => is_scalar($v) ? mb_substr(trim((string) $v), 0, 255) : '',
            };
        }

        return $clean;
    }
}
