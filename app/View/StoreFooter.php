<?php

namespace App\View;

/**
 * Everything a storefront footer template needs, resolved once from the shared $appSettings,
 * so each theme's footer only deals with markup.
 */
class StoreFooter
{
    public const SOCIAL_ICONS = [
        'facebook' => 'fa-facebook-f', 'twitter' => 'fa-twitter', 'instagram' => 'fa-instagram',
        'youtube' => 'fa-youtube', 'linkedin' => 'fa-linkedin', 'tiktok' => 'fa-tiktok',
    ];

    public static function data(array $appSettings): array
    {
        $theme = $appSettings['theme'] ?? [];
        $storeName = $appSettings['store_name'] ?? 'LaravelShop';

        $social = [];
        foreach (static::SOCIAL_ICONS as $network => $icon) {
            $url = trim((string) ($appSettings['social'][$network] ?? ''));
            // only real web links; a stored "javascript:" value must never become a clickable link
            if ($url !== '' && preg_match('#^https?://#i', $url)) {
                $social[] = ['network' => $network, 'icon' => $icon, 'url' => $url];
            }
        }

        $quick = $appSettings['navigation']['footer_quick'] ?? [];
        if (empty($quick)) {
            $quick = [['label' => 'Shop', 'url' => route('shop.index')], ['label' => 'About Us', 'url' => route('about')], ['label' => 'Contact', 'url' => route('contact')]];
        }
        $service = $appSettings['navigation']['footer_service'] ?? [];
        if (empty($service)) {
            $service = [['label' => 'Privacy Policy', 'url' => '/page/privacy-policy'], ['label' => 'Terms & Conditions', 'url' => '/page/terms-conditions']];
        }

        $copyright = (string) ($theme['footer_copyright'] ?? '© {year} {store_name}. All rights reserved.');

        return [
            'style' => in_array($style = (string) ($theme['footer_style'] ?? 'default'), ['default', 'minimal', 'expanded'], true) ? $style : 'default',
            'bg' => $theme['footer_bg_color'] ?? '#111827',
            'text' => $theme['footer_text_color'] ?? '#9ca3af',
            'hover' => $theme['footer_link_hover'] ?? '#ffffff',
            'hideName' => (bool) ($theme['footer_hide_name'] ?? false),
            'showTagline' => (bool) ($theme['footer_show_tagline'] ?? true),
            'showSocial' => (bool) ($theme['footer_show_social'] ?? true),
            'showNewsletter' => (bool) ($theme['footer_show_newsletter'] ?? true) && (bool) ($appSettings['features']['newsletter_enabled'] ?? true),
            'storeName' => $storeName,
            'tagline' => ($appSettings['store_tagline'] ?? '') ?: 'Your one-stop destination for premium products at unbeatable prices. Quality guaranteed.',
            'logo' => !empty($appSettings['store_logo']) ? \Illuminate\Support\Facades\Storage::url($appSettings['store_logo']) : null,
            'address' => $appSettings['store_address'] ?? '',
            'phone' => $appSettings['store_phone'] ?? '',
            'email' => $appSettings['store_email'] ?? '',
            'social' => $social,
            'quick' => $quick,
            'service' => $service,
            'copyright' => str_replace(['{year}', '{store_name}'], [date('Y'), $storeName], $copyright),
            'payments' => [
                ['name' => 'Visa', 'src' => 'https://cdn-icons-png.flaticon.com/32/349/349221.png'],
                ['name' => 'Mastercard', 'src' => 'https://cdn-icons-png.flaticon.com/32/349/349228.png'],
                ['name' => 'PayPal', 'src' => 'https://cdn-icons-png.flaticon.com/32/349/349230.png'],
            ],
        ];
    }
}
