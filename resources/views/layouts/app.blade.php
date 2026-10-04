@php
    $currentLang = $currentLocale ?? app()->getLocale();
    $isRtl = $isRtl ?? in_array($currentLang, ['ar', 'he', 'fa', 'ur']);
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLang }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <title>@yield('title', $appSettings['seo']['meta_title'] ?? $appSettings['store_name'] ?? 'Laravel Shop') - {{ $appSettings['store_name'] ?? 'Premium E-Commerce' }}</title>
    <meta name="description" content="@yield('meta_description', $appSettings['seo']['meta_description'] ?? '')">
    <meta name="keywords" content="@yield('meta_keywords', $appSettings['seo']['meta_keywords'] ?? '')">
    @if(!empty($appSettings['seo']['google_site_verification']))
    <meta name="google-site-verification" content="{{ $appSettings['seo']['google_site_verification'] }}">
    @endif
    
    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="@yield('title', $appSettings['store_name'] ?? 'Laravel Shop')">
    <meta property="og:description" content="@yield('meta_description', $appSettings['seo']['meta_description'] ?? '')">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $appSettings['store_name'] ?? 'Laravel Shop' }}">
    @if(!empty($appSettings['store_logo']))
    <meta property="og:image" content="{{ Storage::url($appSettings['store_logo']) }}">
    @endif
    
    <!-- Favicon -->
    @if(!empty($appSettings['store_favicon']))
    <link rel="icon" type="image/x-icon" href="{{ Storage::url($appSettings['store_favicon']) }}">
    <link rel="shortcut icon" href="{{ Storage::url($appSettings['store_favicon']) }}">
    @endif
    
    <!-- Critical CSS to prevent FOUC (Flash of Unstyled Content) -->
    <style>
        /* Hide body until CSS is ready */
        body { opacity: 0; }
        body.css-loaded { opacity: 1; transition: opacity 0.1s ease-in; }
        
        /* Prevent large images on initial load */
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body { margin: 0; line-height: 1.5; font-family: system-ui, -apple-system, sans-serif; }
        img, video { max-width: 100%; height: auto; display: block; }
        
        /* Critical image styles */
        img { max-width: 100%; height: auto; object-fit: contain; }
        .aspect-square { aspect-ratio: 1/1; }
        .object-contain { object-fit: contain; }
        .object-cover { object-fit: cover; }
        .overflow-hidden { overflow: hidden; }
        
        /* Grid layout critical styles */
        .grid { display: grid; }
        .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .gap-4 { gap: 1rem; }
        .gap-6 { gap: 1.5rem; }
        
        /* Product card critical styles */
        .product-card img { width: 100%; height: 100%; object-fit: contain; }
        .product-image-container { position: relative; aspect-ratio: 1/1; overflow: hidden; }
        
        /* Basic layout */
        .max-w-7xl { max-width: 80rem; margin-left: auto; margin-right: auto; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-8 { padding-top: 2rem; padding-bottom: 2rem; }
        .rounded-2xl { border-radius: 1rem; }
        .bg-white { background-color: #fff; }
        .relative { position: relative; }
        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .h-full { height: 100%; }
        .w-full { width: 100%; }
    </style>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Show body after Tailwind loads -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('css-loaded');
        });
        // Fallback: show body after 500ms even if DOMContentLoaded doesn't fire
        setTimeout(function() {
            document.body.classList.add('css-loaded');
        }, 500);
    </script>
    
    <!-- Google Fonts - Dynamic Font Family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @php
        $fontFamily = $appSettings['font_family'] ?? 'Inter';
        $headingFontSetting = setting('theme.heading_font', '');
        
        // Font URL mapping
        $fontUrls = [
            'Inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
            'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700;800&display=swap',
            'Open Sans' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&display=swap',
            'Poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap',
            'Nunito' => 'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap',
            'Lato' => 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap',
            'Montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap',
            'Playfair Display' => 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&display=swap',
            'Source Sans Pro' => 'https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap',
            'Oswald' => 'https://fonts.googleapis.com/css2?family=Oswald:wght@300;400;500;600;700&display=swap',
            'Raleway' => 'https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700;800&display=swap',
        ];
        
        $fontUrl = $fontUrls[$fontFamily] ?? $fontUrls['Inter'];
    @endphp
    <link href="{{ $fontUrl }}" rel="stylesheet">
    @if($headingFontSetting && $headingFontSetting !== $fontFamily && isset($fontUrls[$headingFontSetting]))
    <link href="{{ $fontUrls[$headingFontSetting] }}" rel="stylesheet">
    @endif
    @if($isRtl)
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @endif
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    @php
        // Generate color shades from primary color
        $primaryColor = $appSettings['primary_color'] ?? '#2563eb';
        $secondaryColor = $appSettings['secondary_color'] ?? '#1e40af';
        $accentColor = $appSettings['accent_color'] ?? '#f59e0b';
        $successColor = setting('theme.success_color', '#10b981');
        $errorColor = setting('theme.error_color', '#ef4444');
        
        // Typography settings
        $headingFont = setting('theme.heading_font', '');
        $baseFontSize = setting('theme.font_size', '16');
        
        // UI Styling settings
        $buttonStyle = setting('theme.button_style', 'rounded');
        $cardStyle = setting('theme.card_style', 'shadow');
        $animationSpeed = setting('theme.animation_speed', 'normal');
        
        // Layout width setting
        $layoutWidth = setting('theme.layout_width', 'default');
        
        // Animation speed values
        $animationDuration = match($animationSpeed) {
            'none' => '0ms',
            'fast' => '150ms',
            'normal' => '300ms',
            'slow' => '500ms',
            default => '300ms',
        };
        
        // Button border radius based on style
        $buttonRadius = match($buttonStyle) {
            'square' => '0px',
            'rounded' => '0.5rem',
            'pill' => '9999px',
            'soft' => '0.25rem',
            default => '0.5rem',
        };
        
        // Convert hex to RGB for shade generation (wrapped to prevent redeclaration)
        if (!function_exists('hexToRgb')) {
            function hexToRgb($hex) {
                $hex = ltrim($hex, '#');
                if (strlen($hex) < 6) return [37, 99, 235]; // Default blue
                return [
                    hexdec(substr($hex, 0, 2)),
                    hexdec(substr($hex, 2, 2)),
                    hexdec(substr($hex, 4, 2))
                ];
            }
        }
        
        if (!function_exists('adjustBrightness')) {
            function adjustBrightness($rgb, $factor) {
                return [
                    min(255, max(0, round($rgb[0] + (255 - $rgb[0]) * $factor))),
                    min(255, max(0, round($rgb[1] + (255 - $rgb[1]) * $factor))),
                    min(255, max(0, round($rgb[2] + (255 - $rgb[2]) * $factor)))
                ];
            }
        }
        
        if (!function_exists('rgbToHex')) {
            function rgbToHex($rgb) {
                return sprintf('#%02x%02x%02x', $rgb[0], $rgb[1], $rgb[2]);
            }
        }
        
        $primaryRgb = hexToRgb($primaryColor);
        $accentRgb = hexToRgb($accentColor);
        
        // Get border radius setting
        $borderRadius = setting('theme.border_radius', 'md');
        $borderRadiusValue = match($borderRadius) {
            'none' => '0px',
            'sm' => '0.25rem',
            'md' => '0.5rem',
            'lg' => '0.75rem',
            'xl' => '1rem',
            default => '0.5rem',
        };
    @endphp
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['{{ $fontFamily }}', 'sans-serif'],
                        @if($headingFont)
                        heading: ['{{ $headingFont }}', 'sans-serif'],
                        @endif
                    },
                    fontSize: {
                        base: '{{ $baseFontSize }}px',
                    },
                    colors: {
                        primary: {
                            50: '{{ rgbToHex(adjustBrightness($primaryRgb, 0.9)) }}',
                            100: '{{ rgbToHex(adjustBrightness($primaryRgb, 0.8)) }}',
                            200: '{{ rgbToHex(adjustBrightness($primaryRgb, 0.6)) }}',
                            300: '{{ rgbToHex(adjustBrightness($primaryRgb, 0.4)) }}',
                            400: '{{ rgbToHex(adjustBrightness($primaryRgb, 0.2)) }}',
                            500: '{{ $primaryColor }}',
                            600: '{{ $primaryColor }}',
                            700: '{{ $secondaryColor }}',
                            800: '{{ $secondaryColor }}',
                            900: '{{ rgbToHex(adjustBrightness($primaryRgb, -0.3)) }}',
                        },
                        accent: {
                            50: '{{ rgbToHex(adjustBrightness($accentRgb, 0.9)) }}',
                            100: '{{ rgbToHex(adjustBrightness($accentRgb, 0.8)) }}',
                            200: '{{ rgbToHex(adjustBrightness($accentRgb, 0.6)) }}',
                            300: '{{ rgbToHex(adjustBrightness($accentRgb, 0.4)) }}',
                            400: '{{ rgbToHex(adjustBrightness($accentRgb, 0.2)) }}',
                            500: '{{ $accentColor }}',
                            600: '{{ $accentColor }}',
                        },
                        success: '{{ $successColor }}',
                        error: '{{ $errorColor }}',
                    },
                    borderRadius: {
                        DEFAULT: '{{ $borderRadiusValue }}',
                    },
                    transitionDuration: {
                        DEFAULT: '{{ $animationDuration }}',
                    }
                }
            }
        }
    </script>
    
    <style>
        [x-cloak] { display: none !important; }
        .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-4 { display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        
        /* Base font size */
        html { font-size: {{ $baseFontSize }}px; }
        body { font-family: '{{ $fontFamily }}', sans-serif; }
        
        /* Heading font */
        @if($headingFont)
        h1, h2, h3, h4, h5, h6, .font-heading { font-family: '{{ $headingFont }}', sans-serif; }
        @endif
        
        /* Button styling based on theme setting */
        .btn, button[type="submit"], .add-to-cart-btn, .buy-now-btn {
            border-radius: {{ $buttonRadius }} !important;
            transition-duration: {{ $animationDuration }};
        }
        
        /* Card styling based on theme setting */
        @if($cardStyle === 'shadow')
        .product-card, .card { box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1); border: none; }
        @elseif($cardStyle === 'border')
        .product-card, .card { box-shadow: none; border: 1px solid #e5e7eb; }
        @elseif($cardStyle === 'flat')
        .product-card, .card { box-shadow: none; border: none; background-color: #f9fafb; }
        @elseif($cardStyle === 'elevated')
        .product-card, .card { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); border: none; }
        @endif
        
        /* Animation speed */
        .transition, [class*="transition-"] {
            transition-duration: {{ $animationDuration }} !important;
        }
        
        /* Mobile-specific fixes */
        @media (max-width: 767px) {
            html, body { overflow-x: hidden; max-width: 100vw; }
            .container, .max-w-7xl, .site-container { padding-left: 1rem; padding-right: 1rem; }
        }
        
        /* Layout Width Settings */
        .site-container {
            width: 100%;
            margin-left: auto;
            margin-right: auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        @media (min-width: 640px) {
            .site-container { padding-left: 1.5rem; padding-right: 1.5rem; }
        }
        
        @media (min-width: 1024px) {
            .site-container { padding-left: 2rem; padding-right: 2rem; }
        }
        
        @if($layoutWidth === 'default')
        /* Default: max-w-7xl (1280px) */
        .site-container { max-width: 80rem; }
        @elseif($layoutWidth === 'wide')
        /* Wide: max-w-screen-2xl (1536px) */
        .site-container { max-width: 96rem; }
        @elseif($layoutWidth === 'full')
        /* Full Width */
        .site-container { max-width: 100%; }
        @elseif($layoutWidth === 'auto')
        /* Auto: Responsive based on screen size */
        .site-container { max-width: 80rem; }
        @media (min-width: 1440px) {
            .site-container { max-width: 96rem; }
        }
        @media (min-width: 1920px) {
            .site-container { max-width: 100%; padding-left: 4rem; padding-right: 4rem; }
        }
        @endif
        
        /* Override max-w-7xl with site-container width */
        @if($layoutWidth === 'wide')
        .max-w-7xl { max-width: 96rem !important; }
        @elseif($layoutWidth === 'full')
        .max-w-7xl { max-width: 100% !important; }
        @elseif($layoutWidth === 'auto')
        @media (min-width: 1440px) {
            .max-w-7xl { max-width: 96rem !important; }
        }
        @media (min-width: 1920px) {
            .max-w-7xl { max-width: 100% !important; padding-left: 4rem; padding-right: 4rem; }
        }
        @endif
        
        /* RTL Support - Fix spacing for Arabic and other RTL languages */
        @if($isRtl)
        html[dir="rtl"] body { font-family: 'Tajawal', 'Cairo', sans-serif; }
        html[dir="rtl"] .space-x-2 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 0.5rem; }
        html[dir="rtl"] .space-x-3 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 0.75rem; }
        html[dir="rtl"] .space-x-4 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 1rem; }
        html[dir="rtl"] .space-x-6 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 1.5rem; }
        html[dir="rtl"] .space-x-8 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 2rem; }
        html[dir="rtl"] .space-x-12 > :not([hidden]) ~ :not([hidden]) { margin-left: 0; margin-right: 3rem; }
        html[dir="rtl"] .mr-1 { margin-right: 0; margin-left: 0.25rem; }
        html[dir="rtl"] .mr-2 { margin-right: 0; margin-left: 0.5rem; }
        html[dir="rtl"] .mr-3 { margin-right: 0; margin-left: 0.75rem; }
        html[dir="rtl"] .mr-4 { margin-right: 0; margin-left: 1rem; }
        html[dir="rtl"] .mr-8 { margin-right: 0; margin-left: 2rem; }
        html[dir="rtl"] .ml-1 { margin-left: 0; margin-right: 0.25rem; }
        html[dir="rtl"] .ml-2 { margin-left: 0; margin-right: 0.5rem; }
        html[dir="rtl"] .ml-3 { margin-left: 0; margin-right: 0.75rem; }
        html[dir="rtl"] .ml-4 { margin-left: 0; margin-right: 1rem; }
        html[dir="rtl"] .ml-8 { margin-left: 0; margin-right: 2rem; }
        html[dir="rtl"] .pl-4 { padding-left: 0; padding-right: 1rem; }
        html[dir="rtl"] .pr-4 { padding-right: 0; padding-left: 1rem; }
        html[dir="rtl"] .left-0 { left: auto; right: 0; }
        html[dir="rtl"] .right-0 { right: auto; left: 0; }
        html[dir="rtl"] .text-left { text-align: right; }
        html[dir="rtl"] .text-right { text-align: left; }
        @endif
    </style>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('styles')
    
    <!-- Google Analytics -->
    @if(!empty($appSettings['seo']['google_analytics']))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $appSettings['seo']['google_analytics'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $appSettings['seo']['google_analytics'] }}');
    </script>
    @endif
    
    <!-- Facebook Pixel -->
    @if(!empty($appSettings['seo']['facebook_pixel']))
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $appSettings['seo']['facebook_pixel'] }}');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $appSettings['seo']['facebook_pixel'] }}&ev=PageView&noscript=1"/></noscript>
    @endif
</head>
<!-- Maintenance Mode Check -->
@if(!empty($appSettings['maintenance_mode']) && !request()->is('admin/*') && !request()->is('login') && !request()->is('register') && !request()->is('password/*') && !(auth()->check() && auth()->user()->is_admin))
<body class="font-sans antialiased bg-gray-100 text-gray-900 flex items-center justify-center min-h-screen">
    <div class="text-center p-8">
        <div class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-tools text-yellow-600 text-4xl"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Under Maintenance</h1>
        <p class="text-gray-600 max-w-md">{{ $appSettings['maintenance_message'] ?? 'We are currently undergoing maintenance. Please check back soon.' }}</p>
        <a href="{{ route('login') }}" class="inline-block mt-6 text-sm text-gray-400 hover:text-gray-600">Admin Login</a>
    </div>
</body>
</html>
@else
<body class="font-sans antialiased bg-gray-50 text-gray-900 layout-{{ $layoutWidth }} min-h-screen flex flex-col" data-layout-width="{{ $layoutWidth }}" x-data="{ toast: null }" @toast.window="toast = $event.detail; setTimeout(() => toast = null, 4000)">
    <x-theme-preview-bar />
    <!-- Toast Notifications -->
    <div x-show="toast" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-4 right-4 z-50">
        <div :class="{
            'bg-green-500': toast?.type === 'success',
            'bg-red-500': toast?.type === 'error',
            'bg-blue-500': toast?.type === 'info',
            'bg-yellow-500': toast?.type === 'warning'
        }" class="text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
            <i :class="{
                'fa-check-circle': toast?.type === 'success',
                'fa-times-circle': toast?.type === 'error',
                'fa-info-circle': toast?.type === 'info',
                'fa-exclamation-triangle': toast?.type === 'warning'
            }" class="fas mr-2"></i>
            <span x-text="toast?.message"></span>
            <button @click="toast = null" class="ml-4 hover:opacity-75">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Screen Size Indicator (Development/Debug) -->
    @if(config('app.debug') && setting('theme.show_screen_indicator', false))
    <div class="fixed bottom-4 left-4 z-50 bg-gray-900 text-white text-xs px-3 py-2 rounded-lg shadow-lg font-mono" x-data="screenIndicator()" x-init="init()">
        <div class="flex items-center gap-2">
            <i :class="icon" class="fas"></i>
            <span x-text="label"></span>
            <span class="text-gray-400">(<span x-text="width"></span>px)</span>
        </div>
    </div>
    <script>
    function screenIndicator() {
        return {
            width: window.innerWidth,
            label: '',
            icon: '',
            init() {
                this.updateIndicator();
                window.addEventListener('resize', () => this.updateIndicator());
            },
            updateIndicator() {
                this.width = window.innerWidth;
                if (this.width < 640) {
                    this.label = 'Mobile (xs)';
                    this.icon = 'fa-mobile-alt';
                } else if (this.width < 768) {
                    this.label = 'Mobile (sm)';
                    this.icon = 'fa-mobile-alt';
                } else if (this.width < 1024) {
                    this.label = 'Tablet (md)';
                    this.icon = 'fa-tablet-alt';
                } else if (this.width < 1280) {
                    this.label = 'Desktop (lg)';
                    this.icon = 'fa-desktop';
                } else if (this.width < 1536) {
                    this.label = 'Desktop (xl)';
                    this.icon = 'fa-desktop';
                } else if (this.width < 1920) {
                    this.label = 'Wide (2xl)';
                    this.icon = 'fa-tv';
                } else {
                    this.label = 'Ultra-wide';
                    this.icon = 'fa-tv';
                }
            }
        }
    }
    </script>
    @endif

    @if(session('success'))
    <script>
        document.addEventListener('alpine:init', () => {
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: '{{ session('success') }}' }}));
            }, 100);
        });
    </script>
    @endif
    
    @if(session('error'))
    <script>
        document.addEventListener('alpine:init', () => {
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: '{{ session('error') }}' }}));
            }, 100);
        });
    </script>
    @endif
    {{-- Header: themes can replace partials/header (or partials/header/base) --}}
    @include('partials.header')

    <!-- Flash Messages -->
    @if(session('success') || session('error') || session('info'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg flex items-center justify-between" x-data="{ show: true }" x-show="show">
                    <span><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</span>
                    <button @click="show = false" class="text-green-700 hover:text-green-900"><i class="fas fa-times"></i></button>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg flex items-center justify-between" x-data="{ show: true }" x-show="show">
                    <span><i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}</span>
                    <button @click="show = false" class="text-red-700 hover:text-red-900"><i class="fas fa-times"></i></button>
                </div>
            @endif
            @if(session('info'))
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg flex items-center justify-between" x-data="{ show: true }" x-show="show">
                    <span><i class="fas fa-info-circle mr-2"></i>{{ session('info') }}</span>
                    <button @click="show = false" class="text-blue-700 hover:text-blue-900"><i class="fas fa-times"></i></button>
                </div>
            @endif
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    {{-- Footer: themes can replace partials/footer --}}
    @include('partials.footer')

    {{-- Auto Translation Script --}}
    <x-auto-translate />
    
    {{-- Cross-Sell Modal --}}
    <div x-data="crossSellModal()" 
         x-show="isOpen" 
         x-cloak
         @cross-sell-modal.window="openModal($event.detail)"
         class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            {{-- Backdrop --}}
            <div x-show="isOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeModal()"
                 class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>
            
            {{-- Modal Content --}}
            <div x-show="isOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block w-full max-w-3xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                
                {{-- Close Button --}}
                <button @click="closeModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
                
                {{-- Success Message --}}
                <div class="flex items-center gap-3 mb-6 pb-4 border-b">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-check text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Added to Cart!</h3>
                        <p class="text-sm text-gray-500">Your item has been added to the cart</p>
                    </div>
                </div>
                
                {{-- Cross-Sell Products --}}
                <template x-if="products.length > 0">
                    <div>
                        <h4 class="text-lg font-semibold mb-4">You May Also Like</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <template x-for="product in products" :key="product.id">
                                <div class="bg-gray-50 rounded-xl overflow-hidden group">
                                    <a :href="'/shop/' + product.slug" class="block aspect-square overflow-hidden">
                                        <img :src="product.image || '/images/no-image.png'" :alt="product.name" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                    <div class="p-3">
                                        <a :href="'/shop/' + product.slug" class="font-medium text-gray-900 hover:text-primary-600 line-clamp-2 text-sm" x-text="product.name"></a>
                                        <div class="flex items-center gap-2 mt-2">
                                            <template x-if="product.formatted_sale_price">
                                                <span>
                                                    <span class="text-primary-600 font-bold text-sm" x-text="product.formatted_sale_price"></span>
                                                    <span class="text-gray-400 text-xs line-through ml-1" x-text="product.formatted_price"></span>
                                                </span>
                                            </template>
                                            <template x-if="!product.formatted_sale_price">
                                                <span class="text-primary-600 font-bold text-sm" x-text="product.formatted_price || product.formatted_current_price"></span>
                                            </template>
                                        </div>
                                        <template x-if="product.product_type === 'variable'">
                                            <a :href="'/shop/' + product.slug" class="mt-2 w-full block text-center py-1.5 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition text-xs">
                                                <i class="fas fa-eye mr-1"></i>View Options
                                            </a>
                                        </template>
                                        <template x-if="product.product_type !== 'variable' && product.is_in_stock">
                                            <button @click="addToCart(product.id)" 
                                                    :disabled="addingToCart === product.id"
                                                    class="mt-2 w-full py-1.5 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition text-xs disabled:opacity-50">
                                                <i :class="addingToCart === product.id ? 'fas fa-spinner fa-spin' : 'fas fa-cart-plus'" class="mr-1"></i>
                                                <span x-text="addingToCart === product.id ? 'Adding...' : 'Add to Cart'"></span>
                                            </button>
                                        </template>
                                        <template x-if="product.product_type !== 'variable' && !product.is_in_stock">
                                            <span class="mt-2 w-full block text-center py-1.5 bg-gray-100 text-red-500 font-medium rounded-lg text-xs">
                                                <i class="fas fa-times-circle mr-1"></i>Out of Stock
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
                
                {{-- Action Buttons --}}
                <div class="flex gap-3 mt-6 pt-4 border-t">
                    <button @click="closeModal()" class="flex-1 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                        Continue Shopping
                    </button>
                    <a href="{{ route('cart.index') }}" class="flex-1 py-3 bg-primary-600 text-white text-center font-semibold rounded-lg hover:bg-primary-700 transition">
                        <i class="fas fa-shopping-cart mr-2"></i>View Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function crossSellModal() {
        return {
            isOpen: false,
            products: [],
            addingToCart: null,
            
            openModal(data) {
                this.products = data.products || [];
                this.isOpen = true;
                document.body.style.overflow = 'hidden';
            },
            
            closeModal() {
                this.isOpen = false;
                document.body.style.overflow = '';
            },
            
            formatPrice(price) {
                // Use server-formatted price if available, otherwise format locally
                const symbol = '{{ $appSettings['currency_symbol'] ?? setting("payment.currency_symbol", "$") }}';
                const position = '{{ setting("payment.currency_position", "before") }}';
                const formatted = parseFloat(price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                return position === 'after' ? formatted + ' ' + symbol : symbol + formatted;
            },
            
            async addToCart(productId) {
                this.addingToCart = productId;
                try {
                    const response = await fetch('{{ route('cart.add') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ product_id: productId, quantity: 1 })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        // Update cart count
                        const cartBadges = document.querySelectorAll('.cart-count-badge');
                        cartBadges.forEach(badge => {
                            badge.textContent = data.cart_count;
                            badge.classList.remove('hidden');
                        });
                        
                        // Remove product from list
                        this.products = this.products.filter(p => p.id !== productId);
                        
                        // Show toast
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { type: 'success', message: data.message }
                        }));
                    } else {
                        window.dispatchEvent(new CustomEvent('toast', { 
                            detail: { type: 'error', message: data.message || 'Failed to add to cart' }
                        }));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    window.dispatchEvent(new CustomEvent('toast', { 
                        detail: { type: 'error', message: 'Failed to add to cart' }
                    }));
                } finally {
                    this.addingToCart = null;
                }
            }
        }
    }
    </script>
    
    @stack('scripts')
    
    {{-- Live Chat Widget --}}
    @if(setting('features.live_chat_enabled'))
        @php
            $chatProvider = setting('features.chat_provider', 'custom');
        @endphp
        
        @if($chatProvider === 'tawk')
            @php
                $tawkPropertyId = setting('features.tawk_property_id', '');
                $tawkWidgetId = setting('features.tawk_widget_id', 'default');
            @endphp
            @if(!empty($tawkPropertyId))
            <!--Start of Tawk.to Script-->
            <script type="text/javascript">
            var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
            (function(){
            var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
            s1.async=true;
            s1.src='https://embed.tawk.to/{{ $tawkPropertyId }}/{{ $tawkWidgetId }}';
            s1.charset='UTF-8';
            s1.setAttribute('crossorigin','*');
            s0.parentNode.insertBefore(s1,s0);
            })();
            </script>
            <!--End of Tawk.to Script-->
            @endif
        @elseif($chatProvider === 'crisp')
            @php
                $crispWebsiteId = setting('features.crisp_website_id', '');
            @endphp
            @if(!empty($crispWebsiteId))
            <!-- Crisp Chat Widget -->
            <script type="text/javascript">
            window.$crisp=[];window.CRISP_WEBSITE_ID="{{ $crispWebsiteId }}";
            (function(){
            d=document;s=d.createElement("script");
            s.src="https://client.crisp.chat/l.js";
            s.async=1;d.getElementsByTagName("head")[0].appendChild(s);
            })();
            </script>
            @endif
        @elseif($chatProvider === 'intercom')
            @php
                $intercomAppId = setting('features.intercom_app_id', '');
            @endphp
            @if(!empty($intercomAppId))
            <!-- Intercom Chat Widget -->
            <script>
            window.intercomSettings = {
                api_base: "https://api-iam.intercom.io",
                app_id: "{{ $intercomAppId }}"
            };
            </script>
            <script>
            (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/{{ $intercomAppId }}';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(document.readyState==='complete'){l();}else if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();
            </script>
            @endif
        @elseif($chatProvider === 'custom')
            @php
                $customChatCode = setting('features.custom_chat_code', '');
            @endphp
            @if(!empty($customChatCode))
            <!-- Custom Chat Widget -->
            {!! $customChatCode !!}
            @endif
        @endif
    @endif
    
    {{-- AI Chat Widget --}}
    @include('components.ai-chat-widget')
    
    @if($layoutWidth === 'auto')
    <!-- Screen Size Detection for Auto Layout -->
    <script>
    (function() {
        function updateLayoutForScreenSize() {
            const width = window.innerWidth;
            const containers = document.querySelectorAll('.max-w-7xl');
            
            containers.forEach(container => {
                // Remove previous width classes
                container.classList.remove('max-w-7xl-auto', 'max-w-screen-2xl-auto', 'max-w-full-auto');
                
                if (width >= 1920) {
                    // Ultra-wide screens: full width with padding
                    container.style.maxWidth = '100%';
                    container.style.paddingLeft = '4rem';
                    container.style.paddingRight = '4rem';
                } else if (width >= 1440) {
                    // Large screens: wide layout
                    container.style.maxWidth = '96rem';
                    container.style.paddingLeft = '';
                    container.style.paddingRight = '';
                } else {
                    // Default for smaller screens
                    container.style.maxWidth = '';
                    container.style.paddingLeft = '';
                    container.style.paddingRight = '';
                }
            });
            
            // Dispatch event for other components that might need to know
            window.dispatchEvent(new CustomEvent('layoutWidthChanged', { 
                detail: { 
                    width: width,
                    breakpoint: width >= 1920 ? 'ultrawide' : (width >= 1440 ? 'wide' : (width >= 1024 ? 'desktop' : (width >= 768 ? 'tablet' : 'mobile')))
                }
            }));
        }
        
        // Run on load and resize
        updateLayoutForScreenSize();
        window.addEventListener('resize', updateLayoutForScreenSize);
    })();
    </script>
    @endif
</body>
</html>
@endif
