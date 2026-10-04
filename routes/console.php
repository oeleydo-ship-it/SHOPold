<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('currency:update-rates {--base=}', function () {
    if (!Setting::get('features.multi_currency', false) || !Setting::get('features.live_currency_converter', false)) {
        $this->info('Multi-currency or live currency converter is disabled. Skipping.');
        return 0;
    }

    $baseCurrency = strtoupper((string) ($this->option('base') ?: Setting::get('payment.currency', 'USD')));

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

    $rates = null;
    $usedApi = '';
    $errors = [];

    // API 1: open.er-api.com
    try {
        $url = "https://open.er-api.com/v6/latest/{$baseCurrency}";
        $response = Http::withoutVerifying()
            ->timeout(15)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ShopperApp/1.0)'])
            ->get($url);
        if ($response->ok()) {
            $payload = $response->json();
            $rates = $payload['rates'] ?? null;
            $usedApi = 'open.er-api.com';
        }
    } catch (\Throwable $e) {
        $errors[] = "open.er-api.com: " . $e->getMessage();
        $this->warn('API 1 failed: ' . $e->getMessage());
    }

    // API 2: frankfurter.app (fallback)
    if (!$rates) {
        try {
            $url = "https://api.frankfurter.app/latest?from={$baseCurrency}";
            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ShopperApp/1.0)'])
                ->get($url);
            if ($response->ok()) {
                $payload = $response->json();
                $rates = $payload['rates'] ?? null;
                $usedApi = 'frankfurter.app';
            }
        } catch (\Throwable $e) {
            $errors[] = "frankfurter.app: " . $e->getMessage();
            $this->warn('API 2 failed: ' . $e->getMessage());
        }
    }

    // API 3: cURL fallback
    if (!$rates && function_exists('curl_init')) {
        $urls = [
            "https://open.er-api.com/v6/latest/{$baseCurrency}",
            "https://api.frankfurter.app/latest?from={$baseCurrency}",
        ];
        foreach ($urls as $url) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ShopperApp/1.0)',
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $payload = json_decode($response, true);
                if (isset($payload['rates'])) {
                    $rates = $payload['rates'];
                    $usedApi = 'cURL fallback';
                    break;
                }
            }
        }
    }

    if (!$rates || !is_array($rates)) {
        $this->error('Failed to fetch exchange rates from all providers: ' . implode(' | ', $errors));
        return 1;
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

    $this->info("Exchange rates updated via {$usedApi} for base {$baseCurrency}. Currencies: " . implode(', ', array_keys($selectedRates)));
    return 0;
})->purpose('Fetch live exchange rates for enabled currencies');


/*
 * Background email worker. Run the scheduler every minute (Windows: scripts/run-scheduler.bat in Task Scheduler,
 * Linux: cron "* * * * * php artisan schedule:run"). Each run records a heartbeat, then sends whatever is queued
 * and exits; nothing is queued unless Settings > Email > "Send emails in the background" is on.
 */
\Illuminate\Support\Facades\Schedule::call(fn () => \App\Support\MailQueue::beat())
    ->name('queue-heartbeat')->everyMinute();

\Illuminate\Support\Facades\Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->name('send-queued-email')->everyMinute()->withoutOverlapping(5)
    ->when(fn () => \App\Support\MailQueue::enabled());
