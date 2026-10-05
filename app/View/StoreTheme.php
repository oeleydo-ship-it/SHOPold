<?php

namespace App\View;

/**
 * Style values for the React storefront's HTML template: colour scales from the theme's primary/accent colours
 * (same maths as the Blade layout's Tailwind config), fonts, base size, button radius, card style and page width.
 * Values come from settings, so the active theme's options (ApplyTheme overlay) are used.
 */
class StoreTheme
{
    public const FONTS = [
        'Inter' => 'Inter:wght@300;400;500;600;700;800',
        'Roboto' => 'Roboto:wght@300;400;500;600;700;800',
        'Open Sans' => 'Open+Sans:wght@300;400;500;600;700;800',
        'Poppins' => 'Poppins:wght@300;400;500;600;700;800',
        'Nunito' => 'Nunito:wght@300;400;500;600;700;800',
        'Lato' => 'Lato:wght@300;400;700;900',
        'Montserrat' => 'Montserrat:wght@300;400;500;600;700;800',
        'Playfair Display' => 'Playfair+Display:wght@400;500;600;700;800',
        'Source Sans Pro' => 'Source+Sans+Pro:wght@300;400;600;700',
        'Oswald' => 'Oswald:wght@300;400;500;600;700',
        'Raleway' => 'Raleway:wght@300;400;500;600;700;800',
    ];

    public static function fontUrl(): string
    {
        $families = array_unique(array_filter([static::font(), static::headingFont()]));
        $q = implode('&', array_map(fn ($f) => 'family=' . (self::FONTS[$f] ?? self::FONTS['Inter']), $families));

        return "https://fonts.googleapis.com/css2?{$q}&display=swap";
    }

    public static function font(): string
    {
        $f = (string) setting('theme.font_family', 'Inter');

        return isset(self::FONTS[$f]) ? $f : 'Inter';
    }

    public static function headingFont(): ?string
    {
        $f = (string) setting('theme.heading_font', '');

        return isset(self::FONTS[$f]) ? $f : null;
    }

    /** CSS custom properties and the few global rules that depend on settings. */
    public static function css(bool $rtl = false): string
    {
        $primary = static::hex(setting('theme.primary_color'), '#2563eb');
        $secondary = static::hex(setting('theme.secondary_color'), '#1e40af');
        $accent = static::hex(setting('theme.accent_color'), '#f59e0b');
        $p = static::rgb($primary);
        $a = static::rgb($accent);

        $vars = [
            '--sf-primary-50' => static::shade($p, 0.9), '--sf-primary-100' => static::shade($p, 0.8),
            '--sf-primary-200' => static::shade($p, 0.6), '--sf-primary-300' => static::shade($p, 0.4),
            '--sf-primary-400' => static::shade($p, 0.2), '--sf-primary-500' => $primary, '--sf-primary-600' => $primary,
            '--sf-primary-700' => $secondary, '--sf-primary-800' => $secondary, '--sf-primary-900' => static::shade($p, -0.3),
            '--sf-accent-50' => static::shade($a, 0.9), '--sf-accent-100' => static::shade($a, 0.8),
            '--sf-accent-200' => static::shade($a, 0.6), '--sf-accent-300' => static::shade($a, 0.4),
            '--sf-accent-400' => static::shade($a, 0.2), '--sf-accent-500' => $accent, '--sf-accent-600' => $accent,
            '--sf-success' => static::hex(setting('theme.success_color'), '#10b981'),
            '--sf-error' => static::hex(setting('theme.error_color'), '#ef4444'),
            '--sf-font' => "'" . static::font() . "'",
            '--sf-heading-font' => "'" . (static::headingFont() ?? static::font()) . "'",
            '--sf-radius' => match ((string) setting('theme.border_radius', 'md')) { 'none' => '0px', 'sm' => '0.25rem', 'lg' => '0.75rem', 'xl' => '1rem', default => '0.5rem' },
            '--sf-button-radius' => match ((string) setting('theme.button_style', 'rounded')) { 'square' => '0px', 'pill' => '9999px', 'soft' => '0.25rem', default => '0.5rem' },
            '--sf-speed' => match ((string) setting('theme.animation_speed', 'normal')) { 'none' => '0ms', 'fast' => '150ms', 'slow' => '500ms', default => '300ms' },
        ];

        $css = ':root{' . implode('', array_map(fn ($k, $v) => "{$k}:{$v};", array_keys($vars), $vars)) . '}';
        $size = max(12, min(20, (int) setting('theme.font_size', 16)));
        $css .= "html{font-size:{$size}px}";
        if ($rtl) {
            $css .= "html[dir=rtl] body{font-family:'Tajawal','Cairo',var(--sf-font),sans-serif}";
        }

        $css .= match ((string) setting('theme.card_style', 'shadow')) {
            'border' => '.sf-card{box-shadow:none;border:1px solid #e5e7eb}',
            'flat' => '.sf-card{box-shadow:none;border-color:transparent;background-color:#f9fafb}',
            'elevated' => '.sf-card{box-shadow:0 10px 15px -3px rgb(0 0 0/.1),0 4px 6px -4px rgb(0 0 0/.1);border-color:transparent}',
            default => '',
        };

        $css .= match ((string) setting('theme.layout_width', 'default')) {
            'wide' => '.sf-container{max-width:96rem}',
            'full' => '.sf-container{max-width:100%}',
            'auto' => '@media(min-width:1440px){.sf-container{max-width:96rem}}@media(min-width:1920px){.sf-container{max-width:100%;padding-inline:4rem}}',
            default => '',
        };

        return $css;
    }

    protected static function hex($value, string $fallback): string
    {
        $value = trim((string) $value);

        return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : $fallback;
    }

    protected static function rgb(string $hex): array
    {
        return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    }

    /** Lighten towards white (factor > 0) or darken (factor < 0), like the Blade layout's adjustBrightness(). */
    protected static function shade(array $rgb, float $factor): string
    {
        $c = array_map(fn ($v) => (int) min(255, max(0, round($v + (255 - $v) * $factor))), $rgb);

        return sprintf('#%02x%02x%02x', ...$c);
    }
}
