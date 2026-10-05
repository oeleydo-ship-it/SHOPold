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

        // During installation / the first migration there is no settings table yet. Mail is configured only
        // when it exists; the view composer below is always registered and checks again when a page renders
        // (so it also works when the table is created later in the same process, e.g. in tests).
        $settingsTableReady = !$this->app->runningInConsole() || Schema::hasTable('settings');

        try {
            if ($settingsTableReady) {
                $this->configureMailSettings();
                \App\Support\MailQueue::apply(); // "send emails in the background" -> database queue
            }

            // the storefront HTML shell and its partials (analytics, live chat) read $appSettings
            View::composer(['storefront', 'partials.*'], function ($view) {
                static $ready = null;
                if (!($ready ??= Schema::hasTable('settings'))) {
                    return;
                }

                $view->with('appSettings', \App\View\StoreSettings::data());
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
