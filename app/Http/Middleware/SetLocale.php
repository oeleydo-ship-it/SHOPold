<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get default language and locale version
        $defaultLocale = Setting::get('store.default_language', 'en');
        $currentLocaleVersion = (int) Setting::get('store.locale_version', 0);
        
        // Check if user's locale version matches current version
        $userLocaleVersion = (int) Session::get('locale_version', 0);
        $cookieLocaleVersion = (int) $request->cookie('locale_version', 0);
        
        // If locale version changed, clear user's locale preferences
        $localeVersionChanged = ($userLocaleVersion !== $currentLocaleVersion) || ($cookieLocaleVersion !== $currentLocaleVersion);
        
        if ($localeVersionChanged) {
            // Clear session locale
            Session::forget('locale');
            Session::put('locale_version', $currentLocaleVersion);
            
            // Queue cookie deletion and set new version cookie
            Cookie::queue(Cookie::forget('locale'));
            Cookie::queue('locale_version', $currentLocaleVersion, 60 * 24 * 365);
        }
        
        // Get available languages - try available_languages_list first (array of codes)
        $availableLanguages = [];
        $storedList = Setting::get('store.available_languages_list');
        
        if ($storedList) {
            // Convert array of codes to associative array
            $allLanguages = [
                'en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German',
                'it' => 'Italian', 'pt' => 'Portuguese', 'nl' => 'Dutch', 'ru' => 'Russian',
                'zh' => 'Chinese', 'ja' => 'Japanese', 'ko' => 'Korean', 'ar' => 'Arabic',
                'hi' => 'Hindi', 'tr' => 'Turkish', 'pl' => 'Polish', 'th' => 'Thai',
                'vi' => 'Vietnamese', 'id' => 'Indonesian', 'ms' => 'Malay', 'fil' => 'Filipino',
            ];
            
            $codes = is_string($storedList) ? (json_decode($storedList, true) ?? ['en']) : $storedList;
            foreach ($codes as $code) {
                $availableLanguages[$code] = $allLanguages[$code] ?? ucfirst($code);
            }
        }
        
        // Fallback to available_languages (object with code => name)
        if (empty($availableLanguages)) {
            $storedLangs = Setting::get('store.available_languages', ['en' => 'English']);
            if (is_string($storedLangs)) {
                $availableLanguages = json_decode($storedLangs, true) ?? ['en' => 'English'];
            } else {
                $availableLanguages = $storedLangs;
            }
        }
        
        // Ensure we have at least English
        if (empty($availableLanguages)) {
            $availableLanguages = ['en' => 'English'];
        }

        // Priority: Session > Cookie > Default (only if version not changed)
        $locale = null;
        $userSelectedLocale = false;

        // Only check user preferences if locale version hasn't changed
        if (!$localeVersionChanged) {
            // 1. Check session first (most reliable after user selection)
            if (Session::has('locale')) {
                $sessionLocale = Session::get('locale');
                // Only use session locale if it's in available languages
                if (array_key_exists($sessionLocale, $availableLanguages)) {
                    $locale = $sessionLocale;
                    $userSelectedLocale = true;
                }
            }
            
            // 2. Check cookie
            if (!$locale && $request->hasCookie('locale')) {
                $cookieLocale = $request->cookie('locale');
                // Only use cookie locale if it's in available languages
                if (array_key_exists($cookieLocale, $availableLanguages)) {
                    $locale = $cookieLocale;
                    $userSelectedLocale = true;
                }
            }
        }

        // 3. If no user selection or version changed, use default language
        if (!$userSelectedLocale) {
            $locale = $defaultLocale;
        }

        // 4. Final fallback to default
        if (!$locale) {
            $locale = $defaultLocale;
        }

        // Validate locale
        if (!array_key_exists($locale, $availableLanguages)) {
            $locale = $defaultLocale;
        }

        // Set the locale
        App::setLocale($locale);
        
        // Store in session for consistency
        Session::put('locale', $locale);

        // Determine if RTL language
        $rtlLanguages = ['ar', 'he', 'fa', 'ur'];
        $isRtl = in_array($locale, $rtlLanguages);

        // Share with all views
        View::share('currentLocale', $locale);
        View::share('availableLanguages', $availableLanguages);
        View::share('isRtl', $isRtl);

        return $next($request);
    }

    /**
     * Parse Accept-Language header
     */
    private function parseAcceptLanguage(string $header): array
    {
        $locales = [];
        $parts = explode(',', $header);
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, ';') !== false) {
                [$locale, $q] = explode(';', $part);
                $locale = trim($locale);
            } else {
                $locale = $part;
            }
            
            // Get just the language code (e.g., 'en' from 'en-US')
            if (strpos($locale, '-') !== false) {
                $locale = explode('-', $locale)[0];
            }
            
            if (!empty($locale) && !in_array($locale, $locales)) {
                $locales[] = $locale;
            }
        }
        
        return $locales;
    }
}
