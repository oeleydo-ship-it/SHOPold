<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Switch the application locale
     */
    public function switch(Request $request, string $locale)
    {
        // Get available languages
        $availableLanguages = $this->getAvailableLanguages();

        if (array_key_exists($locale, $availableLanguages)) {
            // Get current locale version
            $localeVersion = (int) Setting::get('store.locale_version', 0);
            
            // Set locale immediately
            App::setLocale($locale);
            
            // Store in session with version
            Session::put('locale', $locale);
            Session::put('locale_version', $localeVersion);
            Session::save();
            
            // Also set cookies for persistence (1 year)
            $localeCookie = cookie('locale', $locale, 60 * 24 * 365, '/', null, false, false);
            $versionCookie = cookie('locale_version', $localeVersion, 60 * 24 * 365, '/', null, false, false);
            
            return redirect()->back()
                ->withCookie($localeCookie)
                ->withCookie($versionCookie);
        }

        return redirect()->back();
    }
    
    /**
     * Get available languages from settings
     */
    private function getAvailableLanguages(): array
    {
        $allLanguages = [
            'en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German',
            'it' => 'Italian', 'pt' => 'Portuguese', 'nl' => 'Dutch', 'ru' => 'Russian',
            'zh' => 'Chinese', 'ja' => 'Japanese', 'ko' => 'Korean', 'ar' => 'Arabic',
            'hi' => 'Hindi', 'tr' => 'Turkish', 'pl' => 'Polish', 'th' => 'Thai',
            'vi' => 'Vietnamese', 'id' => 'Indonesian', 'ms' => 'Malay', 'fil' => 'Filipino',
        ];
        
        // Try available_languages_list first (array of codes)
        $storedList = Setting::get('store.available_languages_list');
        if ($storedList) {
            $codes = is_string($storedList) ? (json_decode($storedList, true) ?? ['en']) : $storedList;
            $result = [];
            foreach ($codes as $code) {
                $result[$code] = $allLanguages[$code] ?? ucfirst($code);
            }
            return $result;
        }
        
        // Fallback to available_languages
        $storedLangs = Setting::get('store.available_languages', ['en' => 'English']);
        if (is_string($storedLangs)) {
            return json_decode($storedLangs, true) ?? ['en' => 'English'];
        }
        
        return $storedLangs ?: ['en' => 'English'];
    }
}
