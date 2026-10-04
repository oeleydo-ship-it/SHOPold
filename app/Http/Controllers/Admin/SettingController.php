<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Services\ThemeService;
use App\Support\Secrets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    protected ThemeService $themeService;

    /**
     * Sensitive fields that should be encrypted
     */
    protected array $sensitiveFields = Secrets::KEYS;

    /** Languages offered on the localization screen (code => display name). */
    public const LANGUAGES = [
                'en' => 'English', 'es' => 'Spanish (Español)', 'fr' => 'French (Français)',
                'de' => 'German (Deutsch)', 'it' => 'Italian (Italiano)', 'pt' => 'Portuguese (Português)',
                'nl' => 'Dutch (Nederlands)', 'ru' => 'Russian (Русский)', 'zh' => 'Chinese (中文)',
                'ja' => 'Japanese (日本語)', 'ko' => 'Korean (한국어)', 'ar' => 'Arabic (العربية)',
                'hi' => 'Hindi (हिन्दी)', 'tr' => 'Turkish (Türkçe)', 'pl' => 'Polish (Polski)',
                'th' => 'Thai (ไทย)', 'vi' => 'Vietnamese (Tiếng Việt)', 'id' => 'Indonesian (Bahasa Indonesia)',
                'ms' => 'Malay (Bahasa Melayu)', 'fil' => 'Filipino',
            ];

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }


    /**
     * Render a React settings page. $keys maps setting key => default; the current values are
     * passed as `values` keyed by the dotted setting key.
     */
    private function settingsPage(string $component, array $keys, array $extra = [], array $secrets = [])
    {
        $values = [];
        foreach ($keys as $key => $default) {
            $values[$key] = setting($key, $default);
        }

        // Secret values stay on the server; the form only learns whether one is stored.
        $stored = [];
        foreach ($secrets as $key) {
            $stored[$key] = (bool) setting($key);
        }

        return Inertia::render($component, ['values' => $values, 'secrets' => $stored] + $extra);
    }



    /** Every stored setting whose key starts with $prefix (e.g. "theme."), cast to its stored type. */
    private function settingsWithPrefix(string $prefix): array
    {
        $out = [];
        foreach (array_keys(Setting::getAllCached()) as $key) {
            if (str_starts_with($key, $prefix)) {
                $out[$key] = Setting::get($key);
            }
        }

        return $out;
    }

    /**
     * Payment gateway definitions used to render the payment settings screen.
     * field = [key, label, type (text|secret|secret_multiline|number|select|textarea), default, extra]
     * where extra is a placeholder string, or the options array for selects.
     */
    private static function paymentGateways(): array
    {
        $mode = fn ($id, $labels = ['sandbox' => 'Sandbox', 'live' => 'Live']) => ["payment.{$id}_mode", 'Mode', 'select', 'sandbox', $labels];
        $test = ['sandbox' => 'Test', 'live' => 'Live'];

        return [
            ['id' => 'cod', 'title' => 'Cash on Delivery', 'description' => 'Accept cash payment upon delivery', 'docs' => null, 'fields' => [
                ['payment.cod_fee', 'COD Fee', 'number', 0, null],
                ['payment.cod_min_amount', 'Minimum Order Amount', 'number', 0, null],
                ['payment.cod_instructions', 'Instructions', 'textarea', 'Pay with cash upon delivery.', null],
            ]],
            ['id' => 'stripe', 'title' => 'Stripe', 'description' => 'Accept credit/debit cards via Stripe', 'docs' => ['Stripe Dashboard', 'https://dashboard.stripe.com/apikeys'], 'fields' => [
                ['payment.stripe_public_key', 'Publishable Key', 'text', '', 'pk_test_...'],
                ['payment.stripe_secret_key', 'Secret Key', 'secret', '', 'sk_test_...'],
                ['payment.stripe_webhook_secret', 'Webhook Secret', 'secret', '', 'whsec_...'],
            ]],
            ['id' => 'paypal', 'title' => 'PayPal', 'description' => 'Accept payments via PayPal', 'docs' => null, 'fields' => [
                ['payment.paypal_client_id', 'Client ID', 'text', '', null],
                ['payment.paypal_client_secret', 'Client Secret', 'secret', '', null],
                $mode('paypal'),
            ]],
            ['id' => 'razorpay', 'title' => 'Razorpay', 'description' => 'Accept payments via Razorpay (India)', 'docs' => ['Razorpay Dashboard', 'https://dashboard.razorpay.com/app/keys'], 'fields' => [
                ['payment.razorpay_key_id', 'Key ID', 'text', '', 'rzp_test_...'],
                ['payment.razorpay_key_secret', 'Key Secret', 'secret', '', null],
            ]],
            ['id' => 'paytm', 'title' => 'Paytm', 'description' => 'Accept payments via Paytm (India)', 'docs' => ['Paytm Dashboard', 'https://dashboard.paytm.com/next/apikeys'], 'fields' => [
                ['payment.paytm_merchant_id', 'Merchant ID', 'text', '', null],
                ['payment.paytm_merchant_key', 'Merchant Key', 'secret', '', null],
                ['payment.paytm_website', 'Website', 'text', 'WEBSTAGING', null],
                $mode('paytm'),
            ]],
            ['id' => 'instamojo', 'title' => 'Instamojo', 'description' => 'Accept payments via Instamojo (India)', 'docs' => ['Instamojo Dashboard', 'https://www.instamojo.com/integrations'], 'fields' => [
                ['payment.instamojo_api_key', 'API Key', 'text', '', null],
                ['payment.instamojo_auth_token', 'Auth Token', 'secret', '', null],
                $mode('instamojo'),
            ]],
            ['id' => 'payfast', 'title' => 'PayFast', 'description' => 'Accept payments via PayFast (South Africa)', 'docs' => ['PayFast Dashboard', 'https://www.payfast.co.za/dashboard'], 'fields' => [
                ['payment.payfast_merchant_id', 'Merchant ID', 'text', '', null],
                ['payment.payfast_merchant_key', 'Merchant Key', 'secret', '', null],
                ['payment.payfast_passphrase', 'Passphrase', 'secret', '', null],
                $mode('payfast'),
            ]],
            ['id' => 'mercadopago', 'title' => 'Mercado Pago', 'description' => 'Accept payments via Mercado Pago (Latin America)', 'docs' => ['Mercado Pago Developers', 'https://www.mercadopago.com/developers/panel'], 'fields' => [
                ['payment.mercadopago_public_key', 'Public Key', 'text', '', null],
                ['payment.mercadopago_access_token', 'Access Token', 'secret', '', null],
                $mode('mercadopago'),
            ]],
            ['id' => 'authorizenet', 'title' => 'Authorize.net', 'description' => 'Accept credit/debit cards via Authorize.net', 'docs' => ['Authorize.net Merchant Interface', 'https://account.authorize.net/'], 'fields' => [
                ['payment.authorizenet_login_id', 'API Login ID', 'text', '', null],
                ['payment.authorizenet_transaction_key', 'Transaction Key', 'secret', '', null],
                $mode('authorizenet'),
            ]],
            ['id' => 'iyzico', 'title' => 'iyzico', 'description' => 'Accept payments via iyzico (Turkey)', 'docs' => ['iyzico Merchant Panel', 'https://merchant.iyzipay.com/'], 'fields' => [
                ['payment.iyzico_api_key', 'API Key', 'text', '', null],
                ['payment.iyzico_secret_key', 'Secret Key', 'secret', '', null],
                $mode('iyzico'),
            ]],
            ['id' => 'paystack', 'title' => 'Paystack', 'description' => 'Accept payments via Paystack (Africa)', 'docs' => ['Paystack Dashboard', 'https://dashboard.paystack.com/#/settings/developer'], 'fields' => [
                ['payment.paystack_public_key', 'Public Key', 'text', '', 'pk_test_...'],
                ['payment.paystack_secret_key', 'Secret Key', 'secret', '', 'sk_test_...'],
                $mode('paystack', $test),
            ]],
            ['id' => 'flutterwave', 'title' => 'Flutterwave', 'description' => 'Accept payments via Flutterwave (Africa)', 'docs' => ['Flutterwave Dashboard', 'https://dashboard.flutterwave.com/settings/apis'], 'fields' => [
                ['payment.flutterwave_public_key', 'Public Key', 'text', '', null],
                ['payment.flutterwave_secret_key', 'Secret Key', 'secret', '', null],
                ['payment.flutterwave_encryption_key', 'Encryption Key', 'secret', '', null],
                $mode('flutterwave', $test),
            ]],
            ['id' => 'sslcommerz', 'title' => 'SSLCommerz', 'description' => 'Accept payments via SSLCommerz (Bangladesh)', 'docs' => ['SSLCommerz Developer Portal', 'https://developer.sslcommerz.com/'], 'fields' => [
                ['payment.sslcommerz_store_id', 'Store ID', 'text', '', null],
                ['payment.sslcommerz_store_password', 'Store Password', 'secret', '', null],
                $mode('sslcommerz'),
            ]],
            ['id' => 'bkash', 'title' => 'bKash', 'description' => 'Accept payments via bKash (Bangladesh)', 'docs' => ['bKash Developer Portal', 'https://developer.bka.sh/'], 'fields' => [
                ['payment.bkash_app_key', 'App Key', 'text', '', null],
                ['payment.bkash_app_secret', 'App Secret', 'secret', '', null],
                ['payment.bkash_username', 'Username', 'text', '', null],
                ['payment.bkash_password', 'Password', 'secret', '', null],
                $mode('bkash'),
            ]],
            ['id' => 'nagad', 'title' => 'Nagad', 'description' => 'Accept payments via Nagad (Bangladesh)', 'docs' => ['Nagad Merchant Portal', 'https://nagad.com.bd/merchant/'], 'fields' => [
                ['payment.nagad_merchant_id', 'Merchant ID', 'text', '', null],
                ['payment.nagad_merchant_number', 'Merchant Number', 'text', '', null],
                ['payment.nagad_public_key', 'Public Key', 'textarea', '', null],
                ['payment.nagad_private_key', 'Private Key', 'secret_multiline', '', null],
                $mode('nagad'),
            ]],
            ['id' => 'check', 'title' => 'Check / Money Order', 'description' => 'Accept payments via check or money order', 'docs' => null, 'fields' => [
                ['payment.check_payable_to', 'Make Check Payable To', 'text', '', null],
                ['payment.check_mailing_address', 'Mailing Address', 'textarea', '', null],
                ['payment.check_instructions', 'Instructions', 'textarea', 'Please mail your check or money order to the address provided.', null],
            ]],
            ['id' => 'bank', 'title' => 'Bank Transfer', 'description' => 'Accept direct bank transfers', 'docs' => null, 'fields' => [
                ['payment.bank_name', 'Bank Name', 'text', '', null],
                ['payment.bank_account_name', 'Account Name', 'text', '', null],
                ['payment.bank_account_number', 'Account Number', 'text', '', null],
                ['payment.bank_routing_number', 'Routing / SWIFT / IBAN', 'text', '', null],
                ['payment.bank_details', 'Additional Instructions', 'textarea', '', null],
            ]],
        ];
    }

    /**
     * Settings dashboard - General settings
     */
    public function index()
    {
        return $this->settingsPage('Admin/Settings/General', [
            'store.name' => 'Laravel Shop', 'store.tagline' => '', 'store.email' => '', 'store.phone' => '',
            'store.address' => '', 'store.timezone' => 'UTC', 'store.logo' => '', 'store.admin_logo' => '',
            'store.favicon' => '', 'store.hide_name_in_header' => false,
        ]);
    }

    /**
     * Appearance settings (themes, colors)
     */
    public function appearance()
    {
        $raw = setting('theme.hero_slides');
        $slides = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);

        return Inertia::render('Admin/Settings/Appearance', [
            'values' => $this->settingsWithPrefix('theme.'),
            'themes' => collect($this->themeService->getAll())->map(fn ($t) => [
                'name' => $t['name'] ?? '',
                'description' => $t['description'] ?? '',
                'version' => $t['version'] ?? '1.0.0',
                'colors' => $t['colors'] ?? [],
            ]),
            'slides' => collect($slides)->map(fn ($sl) => $sl + [
                'show_features' => false,
            ])->map(fn ($sl) => array_merge($sl, [
                'image' => !empty($sl['image_path']) ? asset('storage/' . $sl['image_path']) : ($sl['image'] ?? ''),
                'show_features' => in_array($sl['show_features'] ?? false, [true, 'true', 1, '1'], true),
            ]))->values(),
            'debug' => (bool) config('app.debug'),
        ]);
    }

    /**
     * Homepage editor
     */
    public function homepage()
    {
        $names = ['hero', 'features', 'categories', 'featured', 'flash_deals', 'top_rated', 'two_column_banners', 'promo', 'arrivals', 'blog', 'newsletter'];
        $stored = array_filter(array_map('trim', explode(',', (string) setting('homepage.section_order', ''))));
        $order = array_values(array_filter($stored, fn ($n) => in_array($n, $names, true)));
        foreach ($names as $n) {
            if (!in_array($n, $order, true)) {
                $order[] = $n; // sections added later go to the end
            }
        }

        return Inertia::render('Admin/Settings/Homepage', [
            'values' => $this->settingsWithPrefix('homepage.'),
            'sectionOrder' => $order,
            'defaultFlashEnd' => now()->addDays(7)->format('Y-m-d\TH:i'),
            'homeUrl' => route('home'),
        ]);
    }

    /**
     * Payment gateway settings
     */
    public function payment()
    {
        $gateways = self::paymentGateways();
        $keys = [
            'payment.currency' => 'USD', 'payment.currency_symbol' => '$', 'payment.currency_position' => 'before',
            'payment.mode' => 'sandbox', 'payment.tax_rate' => 10, 'payment.tax_label' => 'Tax',
        ];
        $secrets = [];
        $enabled = [];
        foreach ($gateways as $g) {
            $key = "payment.{$g['id']}_enabled";
            $keys[$key] = false;
            $enabled[] = $key;
            foreach ($g['fields'] as [$fieldKey, , $type, $default]) {
                if (str_starts_with($type, 'secret')) {
                    $secrets[] = $fieldKey; // never sent to the browser
                } else {
                    $keys[$fieldKey] = $default;
                }
            }
        }

        $base = strtoupper((string) setting('payment.currency', 'USD'));
        $selected = setting('payment.enabled_currencies', [$base]);
        if (is_string($selected)) {
            $selected = json_decode($selected, true) ?? [$base];
        }
        if (!is_array($selected) || empty($selected)) {
            $selected = [$base];
        }

        return $this->settingsPage('Admin/Settings/Payment', $keys, [
            'gateways' => collect($gateways)->map(fn ($g) => [
                'id' => $g['id'], 'title' => $g['title'], 'description' => $g['description'], 'docs' => $g['docs'],
                'enabledKey' => "payment.{$g['id']}_enabled",
                'fields' => collect($g['fields'])->map(fn ($f) => [
                    'key' => $f[0], 'label' => $f[1], 'type' => $f[2],
                    'placeholder' => is_string($f[4]) ? $f[4] : null,
                    'options' => is_array($f[4]) ? $f[4] : null,
                ])->values(),
            ])->values(),
            'enabledKeys' => $enabled,
            'currencies' => \App\Support\Currencies::all(),
            'enabledCurrencies' => array_values($selected),
        ], $secrets);
    }

    /**
     * Shipping settings
     */
    public function shipping()
    {
        $allowed = setting('shipping.allowed_countries', '[]');
        $allowed = is_array($allowed) ? $allowed : (json_decode($allowed, true) ?: []);

        return $this->settingsPage('Admin/Settings/Shipping', [
            'shipping.default_country' => 'US', 'shipping.free_threshold' => 100,
            'shipping.single_country_mode' => false, 'shipping.store_pickup' => false,
            'shipping.international' => false,
        ], [
            'countries' => \App\Support\Countries::all(),
            'allowed' => $allowed,
        ]);
    }

    /**
     * SEO settings
     */
    public function seo()
    {
        return $this->settingsPage('Admin/Settings/Seo', [
            'seo.meta_title' => '', 'seo.meta_description' => '', 'seo.meta_keywords' => '',
            'seo.google_analytics' => '', 'seo.facebook_pixel' => '', 'seo.google_site_verification' => '',
        ]);
    }

    /**
     * Social media settings
     */
    public function social()
    {
        return $this->settingsPage('Admin/Settings/Social', [
            'social.facebook' => '', 'social.twitter' => '', 'social.instagram' => '',
            'social.youtube' => '', 'social.linkedin' => '', 'social.tiktok' => '',
        ]);
    }

    /**
     * Feature toggles
     */
    public function features()
    {
        $json = function ($key, $default) {
            $v = setting($key, $default);
            return is_string($v) ? (json_decode($v, true) ?? $default) : ($v ?? $default);
        };
        $base = strtoupper((string) setting('payment.currency', 'USD'));

        return $this->settingsPage('Admin/Settings/Features', [
            'features.reviews_enabled' => false, 'features.wishlist_enabled' => false, 'features.compare_enabled' => false,
            'features.guest_checkout' => false, 'features.guest_checkout_otp' => true,
            'features.guest_checkout_phone_otp' => false, 'features.guest_otp_expiry' => 10, 'features.guest_otp_length' => '6',
            'features.guest_create_account_option' => true,
            'features.newsletter_enabled' => false, 'features.localization_enabled' => true,
            'features.multi_currency' => false, 'features.live_currency_converter' => false,
            'features.driver_portal' => false,
            'features.blog_enabled' => false, 'features.blog_posts_per_page' => 10, 'features.blog_comments' => true,
            'features.multi_vendor' => false, 'features.vendor_commission' => 10, 'features.vendor_registration' => 'approval',
            'features.vendor_products_approval' => false, 'features.vendor_shipping' => true,
            'features.loyalty_points' => false, 'features.points_per_dollar' => 1, 'features.point_value' => 0.01,
            'features.min_points_redeem' => 100, 'features.points_expiry' => 365,
            'features.signup_bonus_points' => 50, 'features.referral_bonus_points' => 100,
            'features.live_chat_enabled' => false, 'features.chat_provider' => 'tawk',
            'features.tawk_property_id' => '', 'features.tawk_widget_id' => '', 'features.crisp_website_id' => '',
            'features.intercom_app_id' => '', 'features.custom_chat_code' => '',
            'features.ai_chat_enabled' => false, 'features.ai_ssl_verify' => true,
            'features.digital_products' => false, 'features.digital_download_limit' => '',
            'features.digital_download_expiry_days' => '', 'features.digital_max_file_size' => 500,
            'features.digital_require_login' => true,
            'features.digital_allowed_types' => 'pdf,zip,rar,mp3,mp4,epub,mobi,doc,docx,xls,xlsx,ppt,pptx',
        ], [
            'ai' => ['configured' => (bool) setting('ai.api_key'), 'provider' => setting('ai.provider', 'openai')],
            'currency' => [
                'base' => $base,
                'enabled' => array_values((array) $json('payment.enabled_currencies', [])),
                'rates' => (object) (array) $json('payment.exchange_rates', []),
                'ratesBase' => setting('payment.exchange_rates_base', $base),
                'updatedAt' => setting('payment.exchange_rates_updated_at'),
            ],
        ]);
    }

    /**
     * Email settings
     */
    public function email()
    {
        return $this->settingsPage('Admin/Settings/Email', [
            'email.driver' => 'smtp', 'email.smtp_host' => '', 'email.smtp_port' => '587', 'email.smtp_encryption' => 'tls',
            'email.smtp_username' => '', 'email.from_name' => config('app.name'), 'email.from_address' => '',
            'email.reply_to' => '', 'email.admin_email' => '',
            'email.order_confirmation' => true, 'email.order_shipped' => true, 'email.order_delivered' => true,
            'email.admin_new_order' => true, 'email.welcome_email' => true, 'email.password_reset' => true,
            'email.order_status_updates' => true, 'email.queue_enabled' => false,
            'email.vendor_new_order' => true, 'email.vendor_status' => true, 'email.vendor_payout' => true, 'email.driver_assigned' => true,
        ], ['queue' => \App\Support\MailQueue::status()], ['email.smtp_password', 'email.resend_api_key']);
    }

    /**
     * System settings
     */
    public function system()
    {
        return $this->settingsPage('Admin/Settings/System', [
            'system.items_per_page' => 12, 'system.allow_registration' => true,
            'system.require_email_verification' => false, 'system.maintenance_mode' => false,
            'system.maintenance_message' => 'We are currently undergoing maintenance. Please check back soon.',
        ], [
            'info' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'env' => config('app.env'),
            ],
        ]);
    }

    /**
     * Navigation settings
     */
    public function navigation()
    {
        $list = function ($key, array $default) {
            $v = setting($key, '[]');
            $v = is_string($v) ? json_decode($v, true) : $v;
            return is_array($v) && $v ? array_values($v) : $default;
        };

        return Inertia::render('Admin/Settings/Navigation', [
            'header' => $list('navigation.header_menu', [
                ['label' => 'Home', 'url' => '/', 'route' => 'home'],
                ['label' => 'Shop', 'url' => '/shop', 'route' => 'shop.index'],
                ['label' => 'About', 'url' => '/about', 'route' => 'about'],
                ['label' => 'Contact', 'url' => '/contact', 'route' => 'contact'],
            ]),
            'quick' => $list('navigation.footer_quick', [
                ['label' => 'Shop', 'url' => '/shop'], ['label' => 'About Us', 'url' => '/about'], ['label' => 'Contact', 'url' => '/contact'],
            ]),
            'service' => $list('navigation.footer_service', [
                ['label' => 'Privacy Policy', 'url' => '/page/privacy-policy'],
                ['label' => 'Terms & Conditions', 'url' => '/page/terms-conditions'],
            ]),
            'pages' => \App\Models\Page::where('is_active', true)->get(['title', 'slug'])
                ->map(fn ($pg) => ['title' => $pg->title, 'url' => '/page/' . $pg->slug])->values(),
        ]);
    }

    /**
     * AI settings
     */
    public function ai()
    {
        $usage = \App\Models\AiUsage::getMonthlyStats();
        $limit = (int) setting('ai.monthly_limit', 100000);

        return $this->settingsPage('Admin/Settings/Ai', [
            'ai.provider' => 'openai', 'ai.model' => 'gpt-4o-mini', 'ai.temperature' => '0.7',
            'ai.product_generation' => true, 'ai.chat_enabled' => false, 'ai.smart_search' => false,
            'ai.recommendations' => false, 'ai.review_analysis' => false,
            'ai.chat_name' => 'Shop Assistant', 'ai.chat_welcome' => 'Hello! How can I help you today?',
            'ai.chat_system_prompt' => 'You are a helpful shopping assistant for an e-commerce store. Help customers find products, answer questions about orders, shipping, and returns. Be friendly and professional.',
            'ai.monthly_limit' => '100000', 'ai.max_tokens' => '1000',
        ], [
            'usage' => [
                'tokens' => (int) $usage['tokens_used'],
                'requests' => (int) $usage['requests_count'],
                'cost' => number_format($usage['estimated_cost'], 2),
                'limit' => $limit,
                'percent' => $limit > 0 ? min(100, round(($usage['tokens_used'] / $limit) * 100)) : 0,
            ],
        ], ['ai.api_key']);
    }

    /**
     * Shop display settings
     */
    public function shop()
    {
        return Inertia::render('Admin/Settings/Shop', [
            'values' => $this->settingsWithPrefix('shop.'),
            // shown read-only: the text itself is edited through translations
            'shopDescription' => __('messages.shop_description'),
            'shopUrl' => route('shop.index'),
        ]);
    }

    /**
     * Products settings
     */
    public function products()
    {
        return Inertia::render('Admin/Settings/Products', [
            'values' => $this->settingsWithPrefix('products.'),
        ]);
    }

    /**
     * Mobile display settings
     */
    public function mobile()
    {
        $raw = setting('products.mobile_hero_slides', '[]');
        $slides = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);

        return Inertia::render('Admin/Settings/Mobile', [
            'values' => $this->settingsWithPrefix('products.'),
            'slides' => array_values($slides),
        ]);
    }

    /**
     * Localization settings
     */
    public function localization()
    {
        $stored = setting('store.available_languages', ['en' => 'English']);
        $available = is_array($stored) ? $stored : (json_decode($stored, true) ?? ['en' => 'English']);

        return $this->settingsPage('Admin/Settings/Localization', [
            'store.default_language' => 'en', 'store.show_language_switcher' => true, 'store.auto_translate' => false,
        ], [
            'languages' => self::LANGUAGES,
            'available' => array_keys($available),
            'ai' => ['configured' => (bool) setting('ai.api_key'), 'provider' => setting('ai.provider', 'openai')],
        ]);
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $group = $request->input('_group', 'store');
        
        // Get all input and convert underscores back to dots (PHP converts dots to underscores)
        $rawSettings = $request->except(['_token', '_method', '_group', '_boolean_fields']);
        $settings = [];
        
        foreach ($rawSettings as $key => $value) {
            // Convert key format: payment_cod_enabled -> payment.cod_enabled
            $dotKey = preg_replace('/_/', '.', $key, 1);
            $settings[$dotKey] = $value;
        }

        // Only super admins may change settings. Managers use the Shipping page, so they may save
        // shipping.* keys and nothing else (payment keys, mail, AI, themes... stay super-admin only).
        $actor = $request->user();
        if (!$actor->isSuperAdmin()) {
            abort_unless($group === 'shipping' && $actor->hasAdminRole('manager'), 403);
            abort_if($request->allFiles() !== [], 403);
            foreach (array_keys($settings) as $dotKey) {
                abort_unless(str_starts_with($dotKey, 'shipping.'), 403);
            }
        }

        // Handle file uploads - save to Media library
        foreach ($request->allFiles() as $key => $file) {
            if ($file && $file->isValid()) {
                $dotKey = preg_replace('/_/', '.', $key, 1);
                
                // Delete old media if exists
                $oldPath = setting($dotKey);
                if ($oldPath) {
                    $oldMedia = Media::where('path', $oldPath)->first();
                    if ($oldMedia) {
                        $oldMedia->delete();
                    } elseif (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }
                
                // Determine collection based on field type
                $collection = 'settings';
                if (str_contains($key, 'logo')) {
                    $collection = 'logos';
                } elseif (str_contains($key, 'favicon')) {
                    $collection = 'favicons';
                }
                
                // Generate unique filename and store
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('media/' . $collection, $filename, 'public');
                
                // Create Media record
                Media::create([
                    'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'path' => $path,
                    'disk' => 'public',
                    'size' => $file->getSize(),
                    'collection' => $collection,
                    'uploaded_by' => auth()->id(),
                ]);
                
                $settings[$dotKey] = $path;
            }
        }

        // Handle logo from media picker
        if ($request->has('store_logo') && !$request->hasFile('store_logo')) {
            $logoPath = $request->input('store_logo');
            $settings['store.logo'] = $logoPath ?: '';
        }

        // Handle admin logo from media picker
        if ($request->has('store_admin_logo') && !$request->hasFile('store_admin_logo')) {
            $adminLogoPath = $request->input('store_admin_logo');
            $settings['store.admin_logo'] = $adminLogoPath ?: '';
        }

        // Handle favicon from media picker
        if ($request->has('store_favicon') && !$request->hasFile('store_favicon')) {
            $faviconPath = $request->input('store_favicon');
            $settings['store.favicon'] = $faviconPath ?: '';
        }

        // Handle homepage two-column banner images from media picker
        if ($request->has('homepage_banner1_image') && !$request->hasFile('homepage_banner1_image')) {
            $banner1Path = $request->input('homepage_banner1_image');
            $settings['homepage.banner1_image'] = $banner1Path ?: '';
        }
        if ($request->has('homepage_banner2_image') && !$request->hasFile('homepage_banner2_image')) {
            $banner2Path = $request->input('homepage_banner2_image');
            $settings['homepage.banner2_image'] = $banner2Path ?: '';
        }

        // Handle top rated promo image from media picker
        if ($request->has('homepage_top_rated_promo_image') && !$request->hasFile('homepage_top_rated_promo_image')) {
            $topRatedPromoPath = $request->input('homepage_top_rated_promo_image');
            $settings['homepage.top_rated_promo_image'] = $topRatedPromoPath ?: '';
        }

        // Handle hero image from media picker
        if ($request->has('theme_hero_image_path')) {
            $imagePath = $request->input('theme_hero_image_path');
            if (!empty($imagePath)) {
                // Store the path from media library
                $settings['theme.hero_image'] = $imagePath;
            }
        }

        // Handle hero image deletion
        if ($request->has('delete_hero_image')) {
            $settings['theme.hero_image'] = '';
        }

        // Handle hero slides (slider images)
        if ($request->has('theme_hero_slides')) {
            $slidesJson = $request->input('theme_hero_slides');
            if (!empty($slidesJson)) {
                $slides = json_decode($slidesJson, true);
                if (is_array($slides)) {
                    // Clean up the slides data - keep all necessary fields including new ones
                    $cleanSlides = array_map(function($slide) {
                        return [
                            'image' => $slide['image'] ?? '',
                            'image_path' => $slide['image_path'] ?? '',
                            'badge' => $slide['badge'] ?? '',
                            'title' => $slide['title'] ?? '',
                            'subtitle' => $slide['subtitle'] ?? '',
                            'button_text' => $slide['button_text'] ?? 'Shop Now',
                            'button_link' => $slide['button_link'] ?? '/shop',
                            // Secondary button
                            'secondary_button_text' => $slide['secondary_button_text'] ?? '',
                            'secondary_button_link' => $slide['secondary_button_link'] ?? '',
                            // Style options
                            'text_color' => $slide['text_color'] ?? '#1f2937',
                            'text_align' => $slide['text_align'] ?? 'left',
                            'overlay_opacity' => $slide['overlay_opacity'] ?? '0',
                            // Feature highlights
                            'show_features' => !empty($slide['show_features']) && ($slide['show_features'] === true || $slide['show_features'] === 'true' || $slide['show_features'] === 1 || $slide['show_features'] === '1'),
                            'feature_1' => $slide['feature_1'] ?? '',
                            'feature_2' => $slide['feature_2'] ?? '',
                            'feature_3' => $slide['feature_3'] ?? '',
                        ];
                    }, $slides);
                    $settings['theme.hero_slides'] = json_encode($cleanSlides);
                }
            } else {
                $settings['theme.hero_slides'] = '[]';
            }
        }

        // Handle mobile hero slides (separate from desktop)
        if ($request->has('products_mobile_hero_slides')) {
            $slidesJson = $request->input('products_mobile_hero_slides');
            if (!empty($slidesJson)) {
                $slides = json_decode($slidesJson, true);
                if (is_array($slides)) {
                    $cleanSlides = array_map(function($slide) {
                        return [
                            'image_path' => $slide['image_path'] ?? '',
                            'badge' => $slide['badge'] ?? '',
                            'title' => $slide['title'] ?? '',
                            'subtitle' => $slide['subtitle'] ?? '',
                            'button_text' => $slide['button_text'] ?? 'Shop Now',
                            'button_link' => $slide['button_link'] ?? '/shop',
                            'text_color' => $slide['text_color'] ?? '#ffffff',
                            'text_align' => $slide['text_align'] ?? 'left',
                            'overlay_opacity' => $slide['overlay_opacity'] ?? '0.3',
                        ];
                    }, $slides);
                    $settings['products.mobile_hero_slides'] = json_encode($cleanSlides);
                }
            } else {
                $settings['products.mobile_hero_slides'] = '[]';
            }
        }

        // Handle array fields (convert to JSON)
        $arrayFields = ['shipping.allowed_countries'];
        foreach ($arrayFields as $field) {
            $inputKey = str_replace('.', '_', $field);
            if ($request->has($inputKey)) {
                $settings[$field] = json_encode($request->input($inputKey, []));
            }
        }

        // Handle available languages (convert checkbox array to JSON)
        if ($request->has('store_available_languages_list')) {
            $allLanguages = self::LANGUAGES;
            $selectedLanguages = $request->input('store_available_languages_list', []);
            $availableLanguages = [];
            foreach ($selectedLanguages as $code) {
                if (isset($allLanguages[$code])) {
                    $availableLanguages[$code] = $allLanguages[$code];
                }
            }
            // Ensure at least English is available
            if (empty($availableLanguages)) {
                $availableLanguages['en'] = 'English';
            }
            $settings['store.available_languages'] = json_encode($availableLanguages);
        }

        // Handle enabled currencies (multi-currency) - store as JSON array
        if ($request->has('payment_enabled_currencies')) {
            $baseCurrency = strtoupper((string) $request->input('payment_currency', setting('payment.currency', 'USD')));
            $selected = $request->input('payment_enabled_currencies', []);
            if (!is_array($selected)) {
                $selected = [$selected];
            }

            $selected = array_values(array_unique(array_filter(array_map(fn ($c) => strtoupper((string) $c), $selected))));
            if (!in_array($baseCurrency, $selected, true)) {
                $selected[] = $baseCurrency;
            }

            $settings['payment.enabled_currencies'] = json_encode($selected);
        }

        // Handle checkboxes (unchecked = not sent)
        $booleanFields = [
            'payment.cod_enabled', 'payment.stripe_enabled', 'payment.paypal_enabled', 'payment.bank_enabled',
            'payment.razorpay_enabled',
            'shipping.enable_weight_based', 'shipping.store_pickup', 'shipping.international', 'shipping.single_country_mode',
            'features.reviews_enabled', 'features.wishlist_enabled', 'features.compare_enabled',
            'features.guest_checkout', 'features.multi_vendor', 'features.blog_enabled',
            'features.newsletter_enabled', 'features.live_chat_enabled', 'features.loyalty_points',
            'features.blog_comments', 'features.vendor_products_approval', 'features.vendor_shipping',
            'features.ai_chat_enabled', 'features.ai_ssl_verify', 'features.driver_portal', 'features.localization_enabled',
            'features.multi_currency', 'features.live_currency_converter',
            'email.order_confirmation', 'email.order_shipped', 'email.admin_new_order',
            'email.order_delivered', 'email.welcome_email', 'email.password_reset', 'email.order_status_updates', 'email.queue_enabled',
            'email.vendor_new_order', 'email.vendor_status', 'email.vendor_payout', 'email.driver_assigned',
            'system.maintenance_mode', 'system.allow_registration', 'system.require_email_verification',
            'theme.dark_mode', 'theme.sticky_header', 'theme.show_topbar',
            'theme.show_features', 'theme.show_categories', 'theme.show_featured',
            'theme.show_new_arrivals', 'theme.show_newsletter',
            'theme.enable_dark_mode', 'theme.enable_rtl',
            'ai.product_generation', 'ai.chat_enabled', 'ai.smart_search',
            'ai.recommendations', 'ai.review_analysis',
            'homepage.show_features', 'homepage.show_categories', 'homepage.show_featured',
            'homepage.show_promo', 'homepage.show_new_arrivals', 'homepage.show_newsletter',
            'homepage.show_flash_deals', 'homepage.show_top_rated', 'homepage.show_two_column_banners',
            'shop.show_filters', 'shop.show_sorting', 'shop.show_per_page', 'shop.show_grid_options',
            'shop.cross_sell_enabled', 'shop.show_description_desktop', 'shop.show_description_mobile',
            'theme.header_show_wishlist', 'theme.header_show_compare', 'theme.header_show_account_text',
            'store.show_language_switcher', 'store.show_currency_switcher', 'store.auto_translate', 'store.hide_name_in_header',
            'products.stock_management', 'products.hide_out_of_stock', 'products.allow_backorders',
            'products.show_sku', 'products.show_stock_quantity', 'products.review_moderation',
            'products.verified_reviews_only', 'products.show_category', 'products.show_tags',
            'products.show_brand', 'products.show_add_to_cart', 'products.show_buy_now',
            'products.show_compare', 'products.show_wishlist', 'products.show_share',
            'products.share_facebook', 'products.share_twitter', 'products.share_whatsapp',
            'products.share_pinterest', 'products.share_linkedin', 'products.share_email',
            'products.share_copy', 'products.mobile_sticky_buttons', 'products.mobile_show_quantity',
            'products.use_custom_button_colors',
            'products.mobile_hide_rating', 'products.mobile_hide_category', 'products.mobile_hide_stock',
            'products.mobile_show_cart_btn', 'products.homepage_mobile_scroll', 'products.homepage_mobile_compact',
            'products.mobile_show_promo', 'products.mobile_show_search', 'products.mobile_show_language',
            'products.mobile_hero_arrows', 'products.mobile_hero_secondary_btn', 'products.mobile_hero_features',
            'products.mobile_hero_enabled',
        ];

        // Set unchecked checkboxes to 0
        // Only reset boolean fields that are explicitly listed in _boolean_fields input
        // This prevents forms from accidentally resetting boolean fields they don't manage
        $formBooleanFields = $request->input('_boolean_fields', null);
        $managedBooleanFields = [];
        
        if ($formBooleanFields !== null && $formBooleanFields !== '') {
            $managedBooleanFields = array_filter(explode(',', $formBooleanFields));
        }
        
        // If _boolean_fields is provided (even if empty), only reset those specific fields
        // If _boolean_fields is not provided at all (null), use legacy behavior (reset all in group)
        $hasExplicitBooleanFields = $formBooleanFields !== null;
        
        foreach ($booleanFields as $field) {
            if (str_starts_with($field, $group . '.') && !isset($settings[$field])) {
                // Only reset if:
                // 1. No _boolean_fields specified (legacy behavior) OR
                // 2. This field is explicitly listed in _boolean_fields
                if (!$hasExplicitBooleanFields || in_array($field, $managedBooleanFields)) {
                    $settings[$field] = '0';
                }
            }
        }

        // Save settings with error tracking
        $savedCount = 0;
        $failedKeys = [];
        
        foreach ($settings as $key => $value) {
            $type = in_array($key, $booleanFields) ? 'boolean' : 'string';
            
            if (!in_array($key, $booleanFields)) {
                if (is_numeric($value) && str_contains((string)$value, '.')) {
                    $type = 'float';
                } elseif (is_numeric($value) && !str_contains($key, 'color')) {
                    $type = 'integer';
                }
            }

            // Encrypt sensitive fields
            if (in_array($key, $this->sensitiveFields)) {
                // Secrets are always stored as strings, even when the plaintext happens to be numeric
                $type = 'string';

                if (!empty($value)) {
                    // Secrets::encrypt leaves an already-encrypted value alone, but (unlike checking for
                    // an "eyJ" prefix) still encrypts plaintext tokens that merely look like one, e.g. JWTs.
                    $value = Secrets::encrypt((string) $value);
                }
            }

            $saved = Setting::set($key, $value, $type, $group);
            if ($saved) {
                $savedCount++;
            } else {
                $failedKeys[] = $key;
            }
        }

        // Clear all caches comprehensively
        Setting::clearCache();
        
        // Also clear Laravel's config and view cache
        try {
            Artisan::call('config:clear');
            Artisan::call('view:clear');
        } catch (\Exception $e) {
            Log::warning("Failed to clear artisan caches: " . $e->getMessage());
        }
        
        // Log the save operation
        Log::info("Settings update: {$savedCount} saved, " . count($failedKeys) . " failed", [
            'group' => $group,
            'failed_keys' => $failedKeys,
        ]);
        
        // If default language changed, update the version to invalidate user locale cookies
        if (isset($settings['store.default_language'])) {
            $newDefaultLang = $settings['store.default_language'];
            $oldDefaultLang = Setting::get('store.previous_default_language');
            
            if ($oldDefaultLang !== $newDefaultLang) {
                // Store the new default as previous for next comparison
                Setting::set('store.previous_default_language', $newDefaultLang, 'string', 'store');
                
                // Increment locale version to invalidate all user cookies
                $currentVersion = (int) Setting::get('store.locale_version', 0);
                Setting::set('store.locale_version', $currentVersion + 1, 'integer', 'store');
                
                // Clear cache again to ensure new version is used
                Setting::clearCache();
            }
        }

        // Return with appropriate message
        if (!empty($failedKeys)) {
            return redirect()->back()->with('warning', "Settings partially saved. {$savedCount} saved, " . count($failedKeys) . " failed.");
        }
        
        return redirect()->back()->with('success', "Settings updated successfully! ({$savedCount} settings saved)");
    }

    /**
     * Clear application cache
     */
    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Setting::clearCache();

        return redirect()->back()->with('success', 'Cache cleared successfully!');
    }

    /**
     * Refresh exchange rates from API
     */
    public function refreshExchangeRates()
    {
        if (!Setting::get('features.multi_currency', false)) {
            return redirect()->back()->with('error', 'Multi-currency is not enabled.');
        }

        $baseCurrency = strtoupper((string) Setting::get('payment.currency', 'USD'));
        
        $enabledCurrencies = Setting::get('payment.enabled_currencies', [$baseCurrency]);
        if (is_string($enabledCurrencies)) {
            $enabledCurrencies = json_decode($enabledCurrencies, true) ?? [$baseCurrency];
        }
        if (!is_array($enabledCurrencies) || empty($enabledCurrencies)) {
            $enabledCurrencies = [$baseCurrency];
        }
        $enabledCurrencies = array_values(array_unique(array_map(fn ($c) => strtoupper((string) $c), $enabledCurrencies)));

        // Try multiple API providers with fallbacks
        $rates = null;
        $usedApi = '';
        $errors = [];

        // API 1: open.er-api.com (free, no key required)
        try {
            $rates = $this->fetchRatesFromOpenErApi($baseCurrency);
            $usedApi = 'open.er-api.com';
        } catch (\Throwable $e) {
            $errors[] = "open.er-api.com: " . $e->getMessage();
            Log::warning('Exchange rate API 1 failed: ' . $e->getMessage());
        }

        // API 2: frankfurter.app (free, no key required) - fallback
        if (!$rates) {
            try {
                $rates = $this->fetchRatesFromFrankfurter($baseCurrency);
                $usedApi = 'frankfurter.app';
            } catch (\Throwable $e) {
                $errors[] = "frankfurter.app: " . $e->getMessage();
                Log::warning('Exchange rate API 2 failed: ' . $e->getMessage());
            }
        }

        // API 3: Use cURL directly as last resort (some hosts block Laravel HTTP)
        if (!$rates) {
            try {
                $rates = $this->fetchRatesWithCurl($baseCurrency);
                $usedApi = 'cURL fallback';
            } catch (\Throwable $e) {
                $errors[] = "cURL: " . $e->getMessage();
                Log::warning('Exchange rate cURL fallback failed: ' . $e->getMessage());
            }
        }

        if (!$rates || !is_array($rates)) {
            $errorMsg = "Failed to fetch exchange rates from all providers.\n" . implode("\n", $errors);
            Log::error($errorMsg);
            return redirect()->back()->with('error', 'Failed to fetch exchange rates. Your server may be blocking outgoing HTTP requests. Errors: ' . implode(' | ', $errors));
        }

        $selectedRates = [];
        foreach ($enabledCurrencies as $code) {
            if ($code === $baseCurrency) {
                continue;
            }
            if (isset($rates[$code]) && is_numeric($rates[$code]) && (float) $rates[$code] > 0) {
                $selectedRates[$code] = (float) $rates[$code];
            }
        }

        Setting::set('payment.exchange_rates', json_encode($selectedRates), 'string', 'payment');
        Setting::set('payment.exchange_rates_base', $baseCurrency, 'string', 'payment');
        Setting::set('payment.exchange_rates_updated_at', now()->toDateTimeString(), 'string', 'payment');
        Setting::clearCache();

        $ratesList = implode(', ', array_map(fn($k, $v) => "$k: " . number_format($v, 4), array_keys($selectedRates), $selectedRates));
        return redirect()->back()->with('success', "Exchange rates updated via {$usedApi}! Base: {$baseCurrency}. Rates: {$ratesList}");
    }

    /**
     * Fetch rates from open.er-api.com
     */
    protected function fetchRatesFromOpenErApi(string $baseCurrency): ?array
    {
        $url = "https://open.er-api.com/v6/latest/{$baseCurrency}";
        
        $response = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->connectTimeout(4)->timeout(8)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ShopperApp/1.0)'])
            ->get($url);
            
        if (!$response->ok()) {
            throw new \Exception('HTTP ' . $response->status());
        }

        $payload = $response->json();
        return $payload['rates'] ?? null;
    }

    /**
     * Fetch rates from frankfurter.app (ECB data)
     */
    protected function fetchRatesFromFrankfurter(string $baseCurrency): ?array
    {
        $url = "https://api.frankfurter.app/latest?from={$baseCurrency}";
        
        $response = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->connectTimeout(4)->timeout(8)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ShopperApp/1.0)'])
            ->get($url);
            
        if (!$response->ok()) {
            throw new \Exception('HTTP ' . $response->status());
        }

        $payload = $response->json();
        return $payload['rates'] ?? null;
    }

    /**
     * Fetch rates using native cURL (fallback for restrictive hosts)
     */
    protected function fetchRatesWithCurl(string $baseCurrency): ?array
    {
        if (!function_exists('curl_init')) {
            throw new \Exception('cURL extension not available');
        }

        $urls = [
            "https://open.er-api.com/v6/latest/{$baseCurrency}",
            "https://api.frankfurter.app/latest?from={$baseCurrency}",
        ];

        foreach ($urls as $url) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ShopperApp/1.0)',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Cache-Control: no-cache',
                ],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $payload = json_decode($response, true);
                if (isset($payload['rates']) && is_array($payload['rates'])) {
                    return $payload['rates'];
                }
            }

            Log::debug("cURL attempt to {$url} failed: HTTP {$httpCode}, Error: {$error}");
        }

        throw new \Exception('All cURL attempts failed');
    }

    /**
     * Save manually entered exchange rates
     */
    public function saveManualRates(Request $request)
    {
        if (!Setting::get('features.multi_currency', false)) {
            return response()->json(['success' => false, 'message' => 'Multi-currency is not enabled.'], 400);
        }

        $rates = $request->input('rates', []);
        
        if (!is_array($rates) || empty($rates)) {
            return response()->json(['success' => false, 'message' => 'No rates provided.'], 400);
        }

        $baseCurrency = strtoupper((string) Setting::get('payment.currency', 'USD'));
        $validRates = [];

        foreach ($rates as $currency => $rate) {
            $currency = strtoupper((string) $currency);
            $rate = (float) $rate;
            
            if ($currency !== $baseCurrency && $rate > 0) {
                $validRates[$currency] = $rate;
            }
        }

        if (empty($validRates)) {
            return response()->json(['success' => false, 'message' => 'No valid rates provided.'], 400);
        }

        Setting::set('payment.exchange_rates', json_encode($validRates), 'string', 'payment');
        Setting::set('payment.exchange_rates_base', $baseCurrency, 'string', 'payment');
        Setting::set('payment.exchange_rates_updated_at', now()->toDateTimeString() . ' (manual)', 'string', 'payment');
        Setting::clearCache();

        Log::info('Manual exchange rates saved', ['rates' => $validRates, 'base' => $baseCurrency]);

        return response()->json([
            'success' => true,
            'message' => 'Exchange rates saved successfully.',
            'rates' => $validRates,
        ]);
    }

    /**
     * Send test email
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            // Reconfigure mail settings from database before sending
            $this->configureMailerFromDatabase();
            
            // Set a timeout for the SMTP connection
            $timeout = 15; // 15 seconds timeout
            
            Mail::raw('This is a test email from your store. If you received this, your email configuration is working correctly!', function ($message) use ($request, $timeout) {
                $message->to($request->email)
                    ->subject('Test Email - ' . Setting::get('store.name', 'Laravel Shop'));
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $request->email,
            ]);
        } catch (\Swift_TransportException $e) {
            Log::error('Test email transport failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'SMTP connection failed: ' . $this->getReadableSmtpError($e->getMessage()),
            ], 500);
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            Log::error('Test email transport failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'SMTP connection failed: ' . $this->getReadableSmtpError($e->getMessage()),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Test email failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Configure mailer from database settings (for test email)
     */
    protected function configureMailerFromDatabase(): void
    {
        $driver = Setting::get('email.driver', 'smtp');
        
        if ($driver === 'resend') {
            // Configure Resend
            $resendApiKey = $this->decryptSensitiveValue(Setting::get('email.resend_api_key', ''));
            if ($resendApiKey) {
                config(['mail.default' => 'resend']);
                config(['mail.mailers.resend' => ['transport' => 'resend']]);
                config(['services.resend.key' => $resendApiKey]);
            }
        } else {
            // Configure SMTP with timeout
            $mailHost = Setting::get('email.smtp_host');
            if ($mailHost) {
                config(['mail.default' => 'smtp']);
                config(['mail.mailers.smtp.host' => $mailHost]);
                config(['mail.mailers.smtp.port' => (int) Setting::get('email.smtp_port', 587)]);
                config(['mail.mailers.smtp.username' => Setting::get('email.smtp_username', '')]);
                config(['mail.mailers.smtp.password' => $this->decryptSensitiveValue(Setting::get('email.smtp_password', ''))]);
                config(['mail.mailers.smtp.encryption' => Setting::get('email.smtp_encryption', 'tls') ?: null]);
                config(['mail.mailers.smtp.timeout' => 15]); // 15 second timeout
            }
        }
        
        // Configure from address
        $fromAddress = Setting::get('email.from_address');
        $fromName = Setting::get('email.from_name', Setting::get('store.name', 'Laravel Shop'));
        
        if ($fromAddress) {
            config(['mail.from.address' => $fromAddress]);
        }
        if ($fromName) {
            config(['mail.from.name' => $fromName]);
        }
        
        // Purge the mailer instance to force recreation with new config
        Mail::purge('smtp');
        Mail::purge('resend');
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

    /**
     * Convert SMTP errors to user-friendly messages
     */
    protected function getReadableSmtpError(string $error): string
    {
        if (str_contains($error, 'Connection could not be established') || str_contains($error, 'Connection timed out')) {
            return 'Could not connect to SMTP server. Please check your host and port settings.';
        }
        if (str_contains($error, 'Authentication') || str_contains($error, '535') || str_contains($error, 'credentials')) {
            return 'Authentication failed. Please check your username and password.';
        }
        if (str_contains($error, 'certificate') || str_contains($error, 'SSL') || str_contains($error, 'TLS')) {
            return 'SSL/TLS connection failed. Try changing the encryption setting.';
        }
        if (str_contains($error, 'timeout') || str_contains($error, 'timed out')) {
            return 'Connection timed out. The SMTP server is not responding.';
        }
        
        return $error;
    }

    /**
     * Debug settings page (for troubleshooting)
     */
    public function debug()
    {
        $cacheStatus = Setting::getCacheStatus();
        
        // Get some sample settings from both cache and database
        $sampleKeys = [
            'theme.header_style',
            'theme.hero_style',
            'theme.hero_slides',
            'homepage.hero_title',
            'store.name',
            'theme.sticky_header',
            'theme.show_topbar',
        ];
        
        $comparison = [];
        foreach ($sampleKeys as $key) {
            $comparison[$key] = [
                'cached' => Setting::get($key),
                'fresh' => Setting::getFresh($key),
            ];
        }
        
        $heroRaw = Setting::getFresh('theme.hero_slides');
        $slides = $heroRaw ? (json_decode($heroRaw, true) ?: []) : [];

        return Inertia::render('Admin/Settings/Debug', [
            'cacheStatus' => $cacheStatus,
            'comparison' => collect($comparison)->map(fn ($v, $k) => [
                'key' => $k,
                'cached' => is_scalar($v['cached']) || $v['cached'] === null ? $v['cached'] : json_encode($v['cached']),
                'fresh' => is_scalar($v['fresh']) || $v['fresh'] === null ? $v['fresh'] : json_encode($v['fresh']),
            ])->values(),
            'heroRaw' => $heroRaw,
            'slides' => collect($slides)->map(fn ($sl) => [
                'title' => $sl['title'] ?? null,
                'image' => $sl['image_path'] ?? $sl['image'] ?? null,
            ])->values(),
            'headerSettings' => collect([
                'theme.header_style', 'theme.hero_style', 'theme.sticky_header', 'theme.show_topbar', 'theme.header_promo_text',
            ])->map(fn ($k) => ['key' => $k, 'value' => Setting::getFresh($k)])->values(),
            'server' => [
                'cacheDriver' => config('cache.default'),
                'sessionDriver' => config('session.driver'),
                'php' => PHP_VERSION,
                'opcache' => function_exists('opcache_get_status') ? (opcache_get_status() !== false) : false,
                'laravel' => app()->version(),
                'env' => config('app.env'),
            ],
        ]);
    }

    /**
     * Debug cache status (for troubleshooting) - JSON API
     */
    public function debugCache()
    {
        $cacheStatus = Setting::getCacheStatus();
        
        // Get some sample settings from both cache and database
        $sampleKeys = [
            'theme.header_style',
            'theme.hero_style',
            'homepage.hero_title',
            'store.name',
        ];
        
        $comparison = [];
        foreach ($sampleKeys as $key) {
            $comparison[$key] = [
                'cached' => Setting::get($key),
                'fresh' => Setting::getFresh($key),
            ];
        }
        
        return response()->json([
            'cache_status' => $cacheStatus,
            'cache_driver' => config('cache.default'),
            'sample_comparison' => $comparison,
            'php_version' => PHP_VERSION,
            'opcache_enabled' => function_exists('opcache_get_status') ? (opcache_get_status() !== false) : false,
        ]);
    }

    /**
     * Force refresh all settings from database
     */
    public function forceRefresh()
    {
        try {
            // Clear all caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            
            // Force refresh settings
            Setting::refreshAll();
            
            // Clear OPcache if available
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            
            return redirect()->back()->with('success', 'All caches cleared and settings refreshed from database!');
        } catch (\Exception $e) {
            Log::error('Force refresh failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to refresh: ' . $e->getMessage());
        }
    }
}
