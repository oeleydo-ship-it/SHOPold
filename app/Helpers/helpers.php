<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    /**
     * Get or set a setting value
     *
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('settings');
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Setting::set($k, $v);
            }
            return true;
        }

        return Setting::get($key, $default);
    }
}

if (!function_exists('theme_path')) {
    /**
     * Get the path for the active theme
     *
     * @param string $view
     * @return string
     */
    function theme_path(string $view = ''): string
    {
        $theme = setting('theme.active', 'default');
        return "themes.{$theme}" . ($view ? ".{$view}" : '');
    }
}

if (!function_exists('theme_view')) {
    /**
     * Get the view path for the active theme
     *
     * @param string $view
     * @param array $data
     * @return \Illuminate\Contracts\View\View
     */
    function theme_view(string $view, array $data = [])
    {
        return view(theme_path($view), $data);
    }
}


if (!function_exists('format_currency')) {
    /**
     * Format a number as currency using store settings
     *
     * @param float|null $amount
     * @return string
     */
    function format_currency($amount): string
    {
        $amount = (float) ($amount ?? 0);

        $baseCurrency = strtoupper((string) setting('payment.currency', 'USD'));
        $currency = current_currency();

        if (setting('features.multi_currency', false) && $currency !== $baseCurrency) {
            $rates = setting('payment.exchange_rates', []);
            if (is_string($rates)) {
                $rates = json_decode($rates, true) ?? [];
            }

            // Check if rates base matches current base currency
            $ratesBase = strtoupper((string) setting('payment.exchange_rates_base', $baseCurrency));
            
            $rate = $rates[strtoupper($currency)] ?? null;
            if (is_numeric($rate) && (float) $rate > 0 && $ratesBase === $baseCurrency) {
                $amount = $amount * (float) $rate;
            }
        }

        $symbol = currency_symbol($currency);
        $position = setting('payment.currency_position', 'before');
        $decimalSep = setting('payment.decimal_separator', '.');
        $thousandSep = setting('payment.thousand_separator', ',');
        $decimals = (int) setting('payment.decimal_places', 2);

        // Format the number
        $formatted = number_format($amount, $decimals, $decimalSep, $thousandSep);

        // Position the symbol
        if ($position === 'after') {
            return $formatted . ' ' . $symbol;
        }

        return $symbol . $formatted;
    }
}

if (!function_exists('is_feature_enabled')) {
    /**
     * Check if a feature is enabled
     *
     * @param string $feature
     * @return bool
     */
    function is_feature_enabled(string $feature): bool
    {
        return (bool) setting("features.{$feature}_enabled", false);
    }
}

if (!function_exists('current_currency')) {
    /**
     * Get the current currency code
     *
     * @return string
     */
    function current_currency(): string
    {
        $baseCurrency = strtoupper((string) setting('payment.currency', 'USD'));

        if (!setting('features.multi_currency', false)) {
            return $baseCurrency;
        }

        $enabled = setting('payment.enabled_currencies', [$baseCurrency]);
        if (is_string($enabled)) {
            $enabled = json_decode($enabled, true) ?? [$baseCurrency];
        }
        if (!is_array($enabled) || empty($enabled)) {
            $enabled = [$baseCurrency];
        }

        $enabled = array_values(array_unique(array_map(fn ($c) => strtoupper((string) $c), $enabled)));
        if (!in_array($baseCurrency, $enabled, true)) {
            $enabled[] = $baseCurrency;
        }

        $selected = session('currency');
        if (is_string($selected)) {
            $selected = strtoupper($selected);
            if (in_array($selected, $enabled, true)) {
                return $selected;
            }
        }

        return $baseCurrency;
    }
}

if (!function_exists('current_currency_symbol')) {
    /**
     * Get the current currency symbol
     *
     * @return string
     */
    function current_currency_symbol(): string
    {
        return currency_symbol(current_currency());
    }
}

if (!function_exists('currency_symbol')) {
    function currency_symbol(string $currency): string
    {
        $currency = strtoupper($currency);
        $baseCurrency = strtoupper((string) setting('payment.currency', 'USD'));

        if ($currency === $baseCurrency) {
            return setting('payment.currency_symbol', '$');
        }

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'AED' => 'د.إ',
            'INR' => '₹',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'JPY' => '¥',
            'CNY' => '¥',
            'CHF' => 'CHF',
            'SGD' => 'S$',
            'HKD' => 'HK$',
            'NZD' => 'NZ$',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'ZAR' => 'R',
            'MXN' => '$',
            'BRL' => 'R$',
            'PLN' => 'zł',
            'THB' => '฿',
            'MYR' => 'RM',
            'PHP' => '₱',
            'IDR' => 'Rp',
            'KRW' => '₩',
            'TRY' => '₺',
            'RUB' => '₽',
            'SAR' => '﷼',
            'QAR' => '﷼',
            'KWD' => 'د.ك',
            'BHD' => '.د.ب',
            'OMR' => '﷼',
            'EGP' => '£',
            'PKR' => '₨',
            'BDT' => '৳',
            'NGN' => '₦',
            'KES' => 'KSh',
            'GHS' => '₵',
        ];

        return $symbols[$currency] ?? $currency;
    }
}

if (!function_exists('current_locale')) {
    /**
     * Get the current locale
     *
     * @return string
     */
    function current_locale(): string
    {
        return app()->getLocale();
    }
}

if (!function_exists('available_languages')) {
    /**
     * Get available languages
     *
     * @return array
     */
    function available_languages(): array
    {
        $languages = setting('store.available_languages', '{"en":"English"}');
        if (is_string($languages)) {
            return json_decode($languages, true) ?? ['en' => 'English'];
        }
        return $languages;
    }
}

if (!function_exists('ai_translate')) {
    /**
     * Translate text using AI
     *
     * @param string $text
     * @param string|null $targetLanguage
     * @param string $sourceLanguage
     * @return string
     */
    function ai_translate(string $text, ?string $targetLanguage = null, string $sourceLanguage = 'en'): string
    {
        $targetLanguage = $targetLanguage ?? app()->getLocale();
        
        if ($targetLanguage === $sourceLanguage || empty($text)) {
            return $text;
        }

        try {
            $translator = app(\App\Services\TranslationService::class);
            return $translator->translate($text, $targetLanguage, $sourceLanguage);
        } catch (\Exception $e) {
            return $text;
        }
    }
}

if (!function_exists('__t')) {
    /**
     * Translate a string with AI fallback
     * First tries Laravel's built-in translation, then falls back to AI
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    function __t(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        
        // First try Laravel's built-in translation
        $translation = __($key, $replace, $locale);
        
        // If translation key is returned (not translated), try AI
        if ($translation === $key && setting('store.auto_translate', false)) {
            // Extract the actual text from the key (last part after .)
            $parts = explode('.', $key);
            $text = end($parts);
            
            // Convert snake_case to readable text
            $text = str_replace('_', ' ', $text);
            $text = ucfirst($text);
            
            return ai_translate($text, $locale);
        }
        
        return $translation;
    }
}

if (!function_exists('clean_html')) {
    /**
     * Clean HTML content by removing unwanted attributes and comments
     * Useful for sanitizing content pasted from external sources like Word
     *
     * @param string|null $html
     * @return string
     */
    function clean_html(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Remove HTML comments (including <!--StartFragment--> and <!--EndFragment-->)
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        
        // Remove data-* attributes
        $html = preg_replace('/\s*data-[a-zA-Z0-9-]+="[^"]*"/i', '', $html);
        $html = preg_replace("/\s*data-[a-zA-Z0-9-]+='[^']*'/i", '', $html);
        
        // Remove class attributes with mso- (Microsoft Office) classes
        $html = preg_replace('/\s*class="[^"]*mso-[^"]*"/i', '', $html);
        
        // Remove style attributes with mso- styles
        $html = preg_replace('/\s*style="[^"]*mso-[^"]*"/i', '', $html);
        
        // Remove empty style attributes
        $html = preg_replace('/\s*style="\s*"/i', '', $html);
        
        // Remove empty class attributes
        $html = preg_replace('/\s*class="\s*"/i', '', $html);
        
        // Remove xmlns attributes (from Word)
        $html = preg_replace('/\s*xmlns:[a-z]+="[^"]*"/i', '', $html);
        
        // Remove o:p tags (Office paragraph tags)
        $html = preg_replace('/<o:p[^>]*>.*?<\/o:p>/is', '', $html);
        
        // Clean up multiple spaces
        $html = preg_replace('/\s+/', ' ', $html);
        
        // Clean up spaces around tags
        $html = preg_replace('/>\s+</', '><', $html);
        
        // Trim the result
        return trim($html);
    }
}

if (!function_exists('safe_html')) {
    /**
     * Make admin / editor / vendor written HTML safe to print on the storefront.
     *
     * Keeps normal formatting (headings, paragraphs, lists, links, images, tables, quotes, bold/italic...)
     * and the class attribute, and removes everything that can run code: <script>, <iframe>, <style>,
     * event handlers (onclick, onerror...), inline style attributes and javascript:/data: links.
     */
    function safe_html(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        static $sanitizer = null;
        if ($sanitizer === null) {
            $config = (new \Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig())
                ->allowSafeElements()
                ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
                ->allowRelativeLinks()
                ->allowMediaSchemes(['http', 'https'])
                ->allowRelativeMedias()
                ->allowAttribute('class', '*')
                ->allowAttribute('target', 'a')
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->withMaxInputLength(1000000);
            $sanitizer = new \Symfony\Component\HtmlSanitizer\HtmlSanitizer($config);
        }

        return $sanitizer->sanitize($html);
    }
}

if (!function_exists('safe_page_html')) {
    /**
     * Like safe_html(), for CMS pages written by admins/editors: also keeps simple forms (e.g. a contact form
     * pasted into the page), but only ones that post back to this site, and fills in the current CSRF token
     * so they actually submit. Scripts, handlers and external form targets are still removed.
     */
    function safe_page_html(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        static $sanitizer = null;
        if ($sanitizer === null) {
            $config = (new \Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig())
                ->allowSafeElements()
                ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
                ->allowRelativeLinks()
                ->allowMediaSchemes(['http', 'https'])
                ->allowRelativeMedias()
                ->allowAttribute('class', '*')
                ->allowAttribute('target', 'a')
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->allowElement('form', ['action', 'method'])
                ->allowElement('input', ['type', 'name', 'value', 'placeholder', 'required', 'min', 'max', 'maxlength', 'checked', 'id'])
                ->allowElement('textarea', ['name', 'rows', 'placeholder', 'required', 'maxlength', 'id'])
                ->allowElement('select', ['name', 'required', 'id'])
                ->allowElement('option', ['value', 'selected'])
                ->allowElement('button', ['type'])
                ->allowElement('label', ['for'])
                // after allowElement(), which resets each element's attribute list
                ->allowAttribute('class', '*')
                ->withMaxInputLength(1000000);
            $sanitizer = new \Symfony\Component\HtmlSanitizer\HtmlSanitizer($config);
        }

        $clean = $sanitizer->sanitize($html);
        if (!str_contains($clean, '<form')) {
            return $clean;
        }

        $token = e(csrf_token());
        return preg_replace_callback('#<form\b([^>]*)>(.*?)</form>#is', function ($m) use ($token) {
            // only same-site targets: a relative path like "/contact" (not "//host" or "https://...")
            if (preg_match('#\saction="([^"]*)"#i', $m[1], $a) && !preg_match('#^/(?!/)#', html_entity_decode($a[1]))) {
                return $m[2] === '' ? '' : '<div>' . preg_replace('#<input\b[^>]*name="_token"[^>]*>#i', '', $m[2]) . '</div>';
            }
            $inner = preg_replace('#<input\b[^>]*name="_token"[^>]*>#i', '', $m[2]);
            $isPost = preg_match('#\smethod="post"#i', $m[1]);

            return '<form' . $m[1] . '>' . ($isPost ? '<input type="hidden" name="_token" value="' . $token . '" />' : '') . $inner . '</form>';
        }, $clean);
    }
}
