<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\ThemeService;
use App\Services\PaymentGatewayService;
use App\Services\TranslationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('settings', function () {
            return new Setting();
        });

        $this->app->singleton(ThemeService::class, function () {
            return new ThemeService();
        });

        $this->app->alias(ThemeService::class, 'theme');

        $this->app->singleton(PaymentGatewayService::class, function () {
            return new PaymentGatewayService();
        });

        $this->app->alias(PaymentGatewayService::class, 'payment');

        $this->app->singleton(TranslationService::class, function () {
            return new TranslationService();
        });

        $this->app->alias(TranslationService::class, 'ai.translator');
    }

    public function boot(): void
    {
        // Register Blade directive for AI translation
        // @module('wishlist') ... @endmodule: show markup only while that module is switched on (Admin > Modules)
        Blade::if('module', fn (string $key) => \App\Support\Modules::enabled($key));

        Blade::directive('translate', function ($expression) {
            return "<?php echo ai_translate({$expression}); ?>";
        });

        if ($this->app->runningInConsole() && !Schema::hasTable('settings')) {
            return;
        }

        try {
            $this->configureMailSettings();

            View::composer(['layouts.app', 'home', 'shop.*', 'cart.*', 'checkout.*', 'account.*', 'auth.*', 'blog.*', 'page.*'], function ($view) {
                // Get available languages - try available_languages_list first
                $availableLangs = [];
                $storedList = Setting::get('store.available_languages_list');
                
                if ($storedList) {
                    $allLanguages = [
                        'en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German',
                        'it' => 'Italian', 'pt' => 'Portuguese', 'nl' => 'Dutch', 'ru' => 'Russian',
                        'zh' => 'Chinese', 'ja' => 'Japanese', 'ko' => 'Korean', 'ar' => 'Arabic',
                        'hi' => 'Hindi', 'tr' => 'Turkish', 'pl' => 'Polish', 'th' => 'Thai',
                        'vi' => 'Vietnamese', 'id' => 'Indonesian', 'ms' => 'Malay', 'fil' => 'Filipino',
                    ];
                    $codes = is_string($storedList) ? (json_decode($storedList, true) ?? ['en']) : $storedList;
                    foreach ($codes as $code) {
                        $availableLangs[$code] = $allLanguages[$code] ?? ucfirst($code);
                    }
                }
                
                // Fallback to available_languages
                if (empty($availableLangs)) {
                    $storedLangs = Setting::get('store.available_languages', '{"en":"English"}');
                    if (is_string($storedLangs)) {
                        $availableLangs = json_decode($storedLangs, true) ?? ['en' => 'English'];
                    } else {
                        $availableLangs = $storedLangs ?: ['en' => 'English'];
                    }
                }
                
                if (empty($availableLangs)) {
                    $availableLangs = ['en' => 'English'];
                }

                $baseCurrency = strtoupper((string) Setting::get('payment.currency', 'USD'));
                $multiCurrencyEnabled = (bool) Setting::get('features.multi_currency', false);
                $enabledCurrencies = Setting::get('payment.enabled_currencies', [$baseCurrency]);
                if (is_string($enabledCurrencies)) {
                    $enabledCurrencies = json_decode($enabledCurrencies, true) ?? [$baseCurrency];
                }
                if (!is_array($enabledCurrencies) || empty($enabledCurrencies)) {
                    $enabledCurrencies = [$baseCurrency];
                }
                $enabledCurrencies = array_values(array_unique(array_map(fn ($c) => strtoupper((string) $c), $enabledCurrencies)));
                if (!in_array($baseCurrency, $enabledCurrencies, true)) {
                    $enabledCurrencies[] = $baseCurrency;
                }

                $currentCurrency = $baseCurrency;
                if ($multiCurrencyEnabled) {
                    $sessionCurrency = session('currency');
                    if (is_string($sessionCurrency)) {
                        $sessionCurrency = strtoupper($sessionCurrency);
                        if (in_array($sessionCurrency, $enabledCurrencies, true)) {
                            $currentCurrency = $sessionCurrency;
                        }
                    }
                }

                $locale = (string) app()->getLocale();
                $localePrefix = strtolower(substr($locale, 0, 2));
                $rtlLocales = ['ar', 'he', 'fa', 'ur'];
                $isRtl = in_array($localePrefix, $rtlLocales, true);

                $view->with('appSettings', [
                    'store_name' => Setting::get('store.name', config('app.name')),
                    'store_tagline' => Setting::get('store.tagline', ''),
                    'store_email' => Setting::get('store.email', ''),
                    'store_phone' => Setting::get('store.phone', ''),
                    'store_address' => Setting::get('store.address', ''),
                    'store_logo' => Setting::get('store.logo', ''),
                    'store_favicon' => Setting::get('store.favicon', ''),
                    'hide_name_in_header' => Setting::get('store.hide_name_in_header', false),
                    'currency' => $currentCurrency,
                    'currency_symbol' => currency_symbol($currentCurrency),
                    'enabled_currencies' => $enabledCurrencies,
                    'default_language' => Setting::get('store.default_language', 'en'),
                    'available_languages' => $availableLangs,
                    'show_language_switcher' => Setting::get('store.show_language_switcher', true),
                    'show_currency_switcher' => Setting::get('store.show_currency_switcher', true),
                    'current_locale' => app()->getLocale(),
                    'is_rtl' => $isRtl,
                    'currency_position' => Setting::get('payment.currency_position', 'before'),
                    'decimal_separator' => Setting::get('payment.decimal_separator', '.'),
                    'thousand_separator' => Setting::get('payment.thousand_separator', ','),
                    'timezone' => Setting::get('store.timezone', 'UTC'),
                    'tax_rate' => Setting::get('payment.tax_rate', 10),
                    'tax_label' => Setting::get('payment.tax_label', 'Tax'),
                    'tax_included' => Setting::get('payment.tax_included', false),
                    'primary_color' => Setting::get('theme.primary_color', '#2563eb'),
                    'secondary_color' => Setting::get('theme.secondary_color', '#1e40af'),
                    'accent_color' => Setting::get('theme.accent_color', '#f59e0b'),
                    'header_bg' => Setting::get('theme.header_bg', '#ffffff'),
                    'font_family' => Setting::get('theme.font_family', 'Inter'),
                    'button_style' => Setting::get('theme.button_style', 'rounded'),
                    'active_theme' => Setting::get('theme.active', 'default'),
                    'dark_mode' => Setting::get('theme.dark_mode', false),

                    'theme' => [
                        'header_style' => Setting::get('theme.header_style', 'default'),
                        'sticky_header' => Setting::get('theme.sticky_header', true),
                        'show_topbar' => Setting::get('theme.show_topbar', true),
                        'enable_dark_mode' => Setting::get('theme.enable_dark_mode', false),
                        'enable_rtl' => Setting::get('theme.enable_rtl', false),
                        'header_height' => Setting::get('theme.header_height', 'normal'),
                        'header_spacing' => Setting::get('theme.header_spacing', 'normal'),
                        'header_bg_color' => Setting::get('theme.header_bg_color', '#ffffff'),
                        'header_text_color' => Setting::get('theme.header_text_color', '#374151'),
                        'header_hover_color' => Setting::get('theme.header_hover_color', '#2563eb'),
                        'header_promo_text' => Setting::get('theme.header_promo_text', 'Get up to 50% off using *SAVE50* this coupon code'),
                        'header_categories_text' => Setting::get('theme.header_categories_text', 'Browse All Categories'),
                        'header_topbar_bg' => Setting::get('theme.header_topbar_bg', '#1f2937'),
                        'header_categories_bg' => Setting::get('theme.header_categories_bg', '#2563eb'),
                        'header_search_style' => Setting::get('theme.header_search_style', 'simple'),
                        'header_show_wishlist' => Setting::get('theme.header_show_wishlist', true),
                        'header_show_compare' => Setting::get('theme.header_show_compare', true),
                        'header_show_account_text' => Setting::get('theme.header_show_account_text', true),
                        'header_menu_bg' => Setting::get('theme.header_menu_bg', '#ffffff'),
                        'header_menu_text' => Setting::get('theme.header_menu_text', '#374151'),
                        'header_menu_hover' => Setting::get('theme.header_menu_hover', '#2563eb'),
                        'header_menu_active' => Setting::get('theme.header_menu_active', '#2563eb'),
                        'hero_style' => Setting::get('theme.hero_style', 'gradient'),
                        'hero_title' => Setting::get('theme.hero_title', 'Discover Premium Products'),
                        'hero_subtitle' => Setting::get('theme.hero_subtitle', 'Shop the latest trends'),
                        'hero_button_text' => Setting::get('theme.hero_button_text', 'Shop Now'),
                        'hero_button_link' => Setting::get('theme.hero_button_link', '/shop'),
                        'hero_image' => Setting::get('theme.hero_image', ''),
                        'showcase_bg_color' => Setting::get('theme.showcase_bg_color', '#f9fafb'),
                        'showcase_card_bg_color' => Setting::get('theme.showcase_card_bg_color', '#ffffff'),
                        'showcase_image_bg_color' => Setting::get('theme.showcase_image_bg_color', '#f3f4f6'),
                        'showcase_product_source' => Setting::get('theme.showcase_product_source', 'featured'),
                        'showcase_product_count' => Setting::get('theme.showcase_product_count', '4'),
                        'hero_slides' => Setting::get('theme.hero_slides', '[]'),
                        'slider_interval' => Setting::get('theme.slider_interval', '5000'),
                        'slider_transition' => Setting::get('theme.slider_transition', 'fade'),
                        'slider_navigation' => Setting::get('theme.slider_navigation', 'both'),
                        'hero_overlay_color' => Setting::get('theme.hero_overlay_color', '#000000'),
                        'hero_overlay_opacity' => Setting::get('theme.hero_overlay_opacity', '40'),
                        'hero_text_color' => Setting::get('theme.hero_text_color', '#ffffff'),
                        'hero_text_align' => Setting::get('theme.hero_text_align', 'left'),
                        'hero_height' => Setting::get('theme.hero_height', 'medium'),
                        'show_features' => Setting::get('theme.show_features', true),
                        'show_categories' => Setting::get('theme.show_categories', true),
                        'show_featured' => Setting::get('theme.show_featured', true),
                        'show_new_arrivals' => Setting::get('theme.show_new_arrivals', true),
                        'show_newsletter' => Setting::get('theme.show_newsletter', true),
                        'footer_hide_name' => Setting::get('theme.footer_hide_name', false),
                        'footer_show_tagline' => Setting::get('theme.footer_show_tagline', true),
                        'footer_copyright' => Setting::get('theme.footer_copyright', '© {year} {store_name}. All rights reserved.'),
                        'footer_bg_color' => Setting::get('theme.footer_bg_color', '#111827'),
                        'footer_text_color' => Setting::get('theme.footer_text_color', '#9ca3af'),
                        'footer_style' => Setting::get('theme.footer_style', 'default'),
                        'footer_link_hover' => Setting::get('theme.footer_link_hover', '#ffffff'),
                        'footer_show_social' => Setting::get('theme.footer_show_social', true),
                        'footer_show_newsletter' => Setting::get('theme.footer_show_newsletter', true),
                    ],
                    'navigation' => [
                        'header' => json_decode(Setting::get('navigation.header_menu', '[]'), true) ?: [
                            ['label' => 'Home', 'url' => '/', 'route' => 'home'],
                            ['label' => 'Shop', 'url' => '/shop', 'route' => 'shop.index'],
                            ['label' => 'About', 'url' => '/about', 'route' => 'about'],
                            ['label' => 'Contact', 'url' => '/contact', 'route' => 'contact'],
                        ],
                        'footer_quick' => json_decode(Setting::get('navigation.footer_quick', '[]'), true) ?: [
                            ['label' => 'Shop', 'url' => '/shop'],
                            ['label' => 'About Us', 'url' => '/about'],
                            ['label' => 'Contact', 'url' => '/contact'],
                        ],
                        'footer_service' => json_decode(Setting::get('navigation.footer_service', '[]'), true) ?: [
                            ['label' => 'Shipping Policy', 'url' => '/page/shipping-policy'],
                            ['label' => 'Privacy Policy', 'url' => '/page/privacy-policy'],
                        ],
                    ],
                    'seo' => [
                        'meta_title' => Setting::get('seo.meta_title', ''),
                        'meta_description' => Setting::get('seo.meta_description', ''),
                        'meta_keywords' => Setting::get('seo.meta_keywords', ''),
                        'google_analytics' => Setting::get('seo.google_analytics', ''),
                        'facebook_pixel' => Setting::get('seo.facebook_pixel', ''),
                        'google_site_verification' => Setting::get('seo.google_site_verification', ''),
                    ],
                    'social' => [
                        'facebook' => Setting::get('social.facebook', ''),
                        'twitter' => Setting::get('social.twitter', ''),
                        'instagram' => Setting::get('social.instagram', ''),
                        'youtube' => Setting::get('social.youtube', ''),
                        'linkedin' => Setting::get('social.linkedin', ''),
                        'tiktok' => Setting::get('social.tiktok', ''),
                    ],
                    'features' => [
                        'reviews_enabled' => Setting::get('features.reviews_enabled', true),
                        'wishlist_enabled' => Setting::get('features.wishlist_enabled', true),
                        'compare_enabled' => Setting::get('features.compare_enabled', false),
                        'guest_checkout' => Setting::get('features.guest_checkout', true),
                        'newsletter_enabled' => Setting::get('features.newsletter_enabled', true),
                        'live_chat_enabled' => Setting::get('features.live_chat_enabled', false),
                        'localization_enabled' => Setting::get('features.localization_enabled', true),
                        'multi_currency' => $multiCurrencyEnabled,
                        'live_currency_converter' => (bool) Setting::get('features.live_currency_converter', false),
                    ],

                    'homepage' => [
                        'hero_badge' => Setting::get('homepage.hero_badge', 'New Collection ' . date('Y')),
                        'hero_badge_icon' => Setting::get('homepage.hero_badge_icon', 'star'),
                        'hero_title' => Setting::get('homepage.hero_title', 'Discover Premium Products'),
                        'hero_subtitle' => Setting::get('homepage.hero_subtitle', 'Shop the latest trends'),
                        'hero_button_text' => Setting::get('homepage.hero_button_text', 'Shop Now'),
                        'hero_button_link' => Setting::get('homepage.hero_button_link', '/shop'),
                        'hero_secondary_text' => Setting::get('homepage.hero_secondary_text', 'Learn More'),
                        'hero_secondary_link' => Setting::get('homepage.hero_secondary_link', '/about'),
                        'hero_bg_color' => Setting::get('homepage.hero_bg_color', ''),
                        'hero_text_color' => Setting::get('homepage.hero_text_color', ''),
                        'show_features' => Setting::get('homepage.show_features', true),
                        'show_categories' => Setting::get('homepage.show_categories', true),
                        'show_featured' => Setting::get('homepage.show_featured', true),
                        'show_promo' => Setting::get('homepage.show_promo', true),
                        'show_new_arrivals' => Setting::get('homepage.show_new_arrivals', true),
                        'show_newsletter' => Setting::get('homepage.show_newsletter', true),
                        'feature_1_icon' => Setting::get('homepage.feature_1_icon', 'truck'),
                        'feature_1_title' => Setting::get('homepage.feature_1_title', 'Free Shipping'),
                        'feature_1_desc' => Setting::get('homepage.feature_1_desc', 'On orders over $50'),
                        'feature_2_icon' => Setting::get('homepage.feature_2_icon', 'undo'),
                        'feature_2_title' => Setting::get('homepage.feature_2_title', 'Easy Returns'),
                        'feature_2_desc' => Setting::get('homepage.feature_2_desc', '30-day return policy'),
                        'feature_3_icon' => Setting::get('homepage.feature_3_icon', 'shield-alt'),
                        'feature_3_title' => Setting::get('homepage.feature_3_title', 'Secure Payment'),
                        'feature_3_desc' => Setting::get('homepage.feature_3_desc', '100% secure checkout'),
                        'feature_4_icon' => Setting::get('homepage.feature_4_icon', 'headset'),
                        'feature_4_title' => Setting::get('homepage.feature_4_title', '24/7 Support'),
                        'feature_4_desc' => Setting::get('homepage.feature_4_desc', 'Dedicated support'),
                        'features_bg_color' => Setting::get('homepage.features_bg_color', ''),
                        'categories_label' => Setting::get('homepage.categories_label', 'Browse'),
                        'categories_title' => Setting::get('homepage.categories_title', 'Shop by Category'),
                        'categories_desc' => Setting::get('homepage.categories_desc', 'Explore our curated collection'),
                        'categories_bg_color' => Setting::get('homepage.categories_bg_color', ''),
                        'categories_item_font_size' => Setting::get('homepage.categories_item_font_size', '12'),
                        'categories_item_font_size_md' => Setting::get('homepage.categories_item_font_size_md', '14'),
                        'categories_item_font_weight' => Setting::get('homepage.categories_item_font_weight', '500'),
                        'categories_item_text_color' => Setting::get('homepage.categories_item_text_color', '#1f2937'),
                        'categories_item_hover_color' => Setting::get('homepage.categories_item_hover_color', '#2563eb'),
                        'featured_label' => Setting::get('homepage.featured_label', 'Handpicked'),
                        'featured_title' => Setting::get('homepage.featured_title', 'Featured Products'),
                        'featured_count' => Setting::get('homepage.featured_count', '8'),
                        'featured_button' => Setting::get('homepage.featured_button', 'View All'),
                        'featured_bg_color' => Setting::get('homepage.featured_bg_color', ''),
                        'promo_badge' => Setting::get('homepage.promo_badge', 'Limited Time'),
                        'promo_discount' => Setting::get('homepage.promo_discount', '50'),
                        'promo_title' => Setting::get('homepage.promo_title', 'Up to 50% Off'),
                        'promo_desc' => Setting::get('homepage.promo_desc', 'Shop now and save big!'),
                        'promo_button' => Setting::get('homepage.promo_button', 'Shop Sale'),
                        'promo_link' => Setting::get('homepage.promo_link', '/shop?sale=1'),
                        'promo_bg_type' => Setting::get('homepage.promo_bg_type', 'color'),
                        'promo_bg_color' => Setting::get('homepage.promo_bg_color', ''),
                        'promo_bg_image' => Setting::get('homepage.promo_bg_image', ''),
                        'arrivals_label' => Setting::get('homepage.arrivals_label', 'Just In'),
                        'arrivals_title' => Setting::get('homepage.arrivals_title', 'New Arrivals'),
                        'arrivals_count' => Setting::get('homepage.arrivals_count', '8'),
                        'arrivals_button' => Setting::get('homepage.arrivals_button', 'View All'),
                        'arrivals_bg_color' => Setting::get('homepage.arrivals_bg_color', ''),
                        'newsletter_badge' => Setting::get('homepage.newsletter_badge', 'Stay Updated'),
                        'newsletter_title' => Setting::get('homepage.newsletter_title', 'Subscribe to Our Newsletter'),
                        'newsletter_desc' => Setting::get('homepage.newsletter_desc', 'Get exclusive deals'),
                        'newsletter_placeholder' => Setting::get('homepage.newsletter_placeholder', 'Enter your email'),
                        'newsletter_button' => Setting::get('homepage.newsletter_button', 'Subscribe'),
                        'newsletter_note' => Setting::get('homepage.newsletter_note', 'No spam'),
                        'newsletter_bg_type' => Setting::get('homepage.newsletter_bg_type', 'color'),
                        'newsletter_bg_color' => Setting::get('homepage.newsletter_bg_color', ''),
                        'newsletter_bg_image' => Setting::get('homepage.newsletter_bg_image', ''),
                        'font_family' => Setting::get('homepage.font_family', ''),
                        'heading_font' => Setting::get('homepage.heading_font', ''),
                        'section_order' => Setting::get('homepage.section_order', 'hero,features,categories,featured,promo,arrivals,newsletter'),
                        'show_flash_deals' => Setting::get('homepage.show_flash_deals', true),
                        'show_top_rated' => Setting::get('homepage.show_top_rated', true),
                        'top_rated_title' => Setting::get('homepage.top_rated_title', 'Top Rated'),
                        'top_rated_promo_badge' => Setting::get('homepage.top_rated_promo_badge', 'GET 50% DISCOUNT'),
                        'top_rated_promo_title' => Setting::get('homepage.top_rated_promo_title', 'Get Up to 20% off for Computer Gadget'),
                        'top_rated_promo_desc' => Setting::get('homepage.top_rated_promo_desc', 'We collect latest iphone mobile series and apple gadget collection.'),
                        'top_rated_promo_button' => Setting::get('homepage.top_rated_promo_button', 'Explore More'),
                        'top_rated_promo_link' => Setting::get('homepage.top_rated_promo_link', '/shop?category=electronics'),
                        'top_rated_promo_image' => Setting::get('homepage.top_rated_promo_image', ''),
                        'banner1_badge' => Setting::get('homepage.banner1_badge', 'GET 50% DISCOUNT'),
                        'banner1_title' => Setting::get('homepage.banner1_title', 'Fresh Grocery Items Now Available'),
                        'banner1_button' => Setting::get('homepage.banner1_button', 'Shop Now'),
                        'banner1_link' => Setting::get('homepage.banner1_link', '/shop?category=grocery'),
                        'banner1_bg_type' => Setting::get('homepage.banner1_bg_type', 'image'),
                        'banner1_bg_color' => Setting::get('homepage.banner1_bg_color', ''),
                        'banner1_image' => Setting::get('homepage.banner1_image', ''),
                        'banner2_badge' => Setting::get('homepage.banner2_badge', 'GET 50% DISCOUNT'),
                        'banner2_title' => Setting::get('homepage.banner2_title', 'New Home Appliance For Your Home'),
                        'banner2_button' => Setting::get('homepage.banner2_button', 'Shop Now'),
                        'banner2_link' => Setting::get('homepage.banner2_link', '/shop?category=appliances'),
                        'banner2_bg_type' => Setting::get('homepage.banner2_bg_type', 'image'),
                        'banner2_bg_color' => Setting::get('homepage.banner2_bg_color', ''),
                        'banner2_image' => Setting::get('homepage.banner2_image', ''),
                        'show_two_column_banners' => Setting::get('homepage.show_two_column_banners', true),
                    ],
                    'payment' => [
                        'cod_enabled' => Setting::get('payment.cod_enabled', true),
                        'stripe_enabled' => Setting::get('payment.stripe_enabled', false),
                        'paypal_enabled' => Setting::get('payment.paypal_enabled', false),
                        'bank_enabled' => Setting::get('payment.bank_enabled', false),
                    ],
                    'shipping' => [
                        'free_threshold' => Setting::get('shipping.free_threshold', 100),
                        'flat_rate' => Setting::get('shipping.flat_rate', 10),
                        'store_pickup' => Setting::get('shipping.store_pickup', false),
                    ],
                    'maintenance_mode' => Setting::get('system.maintenance_mode', false),
                    'maintenance_message' => Setting::get('system.maintenance_message', 'Under maintenance.'),
                ]);
            });
        } catch (\Exception $e) {
            // Settings table may not exist yet
        }
    }

    protected function configureMailSettings(): void
    {
        try {
            $driver = Setting::get('email.driver', 'smtp');
            
            if ($driver === 'resend') {
                // Configure Resend
                $resendApiKey = $this->decryptSensitiveValue(Setting::get('email.resend_api_key', ''));
                if ($resendApiKey) {
                    Config::set('mail.default', 'resend');
                    Config::set('mail.mailers.resend', [
                        'transport' => 'resend',
                    ]);
                    Config::set('services.resend.key', $resendApiKey);
                }
            } else {
                // Configure SMTP
                $mailHost = Setting::get('email.smtp_host');
                if ($mailHost) {
                    Config::set('mail.default', 'smtp');
                    Config::set('mail.mailers.smtp.host', $mailHost);
                    Config::set('mail.mailers.smtp.port', (int) Setting::get('email.smtp_port', 587));
                    Config::set('mail.mailers.smtp.username', Setting::get('email.smtp_username', ''));
                    Config::set('mail.mailers.smtp.password', $this->decryptSensitiveValue(Setting::get('email.smtp_password', '')));
                    $encryption = Setting::get('email.smtp_encryption', 'tls');
                    Config::set('mail.mailers.smtp.encryption', $encryption ?: null);
                    Config::set('mail.mailers.smtp.timeout', 15); // 15 second timeout
                }
            }
            
            $fromAddress = Setting::get('email.from_address');
            $fromName = Setting::get('email.from_name');
            if ($fromAddress) {
                Config::set('mail.from.address', $fromAddress);
            }
            if ($fromName) {
                Config::set('mail.from.name', $fromName);
            }
        } catch (\Exception $e) {
            Log::error('Failed to configure mail settings: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt a sensitive value if it appears to be encrypted
     */
    protected function decryptSensitiveValue(?string $value): string
    {
        if (empty($value)) {
            return '';
        }
        
        // Check if value appears to be encrypted (base64 encoded JSON from Laravel's Crypt)
        if (str_starts_with($value, 'eyJ')) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                Log::warning('Failed to decrypt sensitive value: ' . $e->getMessage());
                return $value;
            }
        }
        
        return $value;
    }
}
