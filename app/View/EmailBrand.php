<?php

namespace App\View;

use App\Services\ThemeService;
use Illuminate\Support\Facades\Storage;

/**
 * Branding for outgoing emails: store name, logo, contact details and the active theme's colours.
 * Emails are often sent outside a storefront request (queue, admin actions), so the theme values are
 * resolved here instead of relying on the ApplyTheme middleware overlay.
 */
class EmailBrand
{
    public static function data(): array
    {
        $theme = [];
        try {
            $themes = app(ThemeService::class);
            $theme = $themes->values($themes->getActivated());
        } catch (\Throwable $e) {
            report($e); // an email must still go out if the theme is broken
        }

        $logo = (string) setting('store.logo', '');
        $social = [];
        foreach (['facebook', 'instagram', 'twitter', 'youtube', 'tiktok'] as $network) {
            $url = trim((string) setting('social.' . $network, ''));
            if ($url !== '' && preg_match('#^https?://#i', $url)) {
                $social[$network] = $url;
            }
        }

        return [
            'name' => (string) setting('store.name', config('app.name')),
            'logo' => $logo !== '' ? (preg_match('#^https?://#i', $logo) ? $logo : url(Storage::url($logo))) : null,
            'primary' => static::color($theme['primary_color'] ?? setting('theme.primary_color'), '#2563eb'),
            'secondary' => static::color($theme['secondary_color'] ?? setting('theme.secondary_color'), '#1e40af'),
            'address' => (string) setting('store.address', ''),
            'email' => (string) setting('store.email', ''),
            'phone' => (string) setting('store.phone', ''),
            'url' => url('/'),
            'social' => $social,
        ];
    }

    /** Only plain hex colours reach the inline styles. */
    protected static function color($value, string $fallback): string
    {
        $value = trim((string) $value);

        return preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value) ? $value : $fallback;
    }

    /** Links stored with an order (tracking URL) are only used when they are real web links. */
    public static function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        return $url !== '' && preg_match('#^https?://#i', $url) ? $url : null;
    }
}
