{{--
    HTML template for the React (Inertia) storefront. Server-rendered SEO tags come from the page's "seo" prop so
    search engines get them even when server-side rendering is off; @inertiaHead adds what the page sets with <Head>.
--}}
@php
    $sf = $page['props']['sf'] ?? [];
    $seo = array_merge($sf['seo'] ?? [], $page['props']['seo'] ?? []);
    $rtl = (bool) ($sf['locale']['rtl'] ?? false);
    $storeName = $sf['store']['name'] ?? config('app.name');
    $title = !empty($page['props']['seo']['title']) ? $page['props']['seo']['title'] . ' - ' . $storeName : ($seo['title'] ?? $storeName);
    // Server-side render (cached for @inertia / @inertiaHead). When it worked, its <head> tags replace the fallbacks below.
    $ssr = app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @unless($ssr)
        {{-- Without server-side rendering: the same tags <Seo> sets, so crawlers still get them (React replaces them in the browser). --}}
        <title data-inertia>{{ $title }}</title>
        <meta data-inertia name="description" content="{{ $seo['description'] ?? '' }}">
        @if(!empty($seo['canonical']))<link data-inertia rel="canonical" href="{{ $seo['canonical'] }}">@endif
        <meta data-inertia property="og:title" content="{{ $title }}">
        <meta data-inertia property="og:description" content="{{ $seo['description'] ?? '' }}">
        @if(!empty($page['props']['seo']['robots']))<meta data-inertia name="robots" content="{{ $page['props']['seo']['robots'] }}">@endif
    @endunless
    @if(!empty($seo['keywords']))<meta name="keywords" content="{{ $seo['keywords'] }}">@endif
    @if(setting('seo.google_site_verification'))<meta name="google-site-verification" content="{{ setting('seo.google_site_verification') }}">@endif
    <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
    <meta property="og:site_name" content="{{ $storeName }}">
    @if(!empty($seo['image']) || !empty($sf['store']['logo']))<meta property="og:image" content="{{ $seo['image'] ?? url($sf['store']['logo']) }}">@endif
    @if(!empty($sf['store']['favicon']))<link rel="icon" href="{{ $sf['store']['favicon'] }}">@endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ \App\View\StoreTheme::fontUrl() }}" rel="stylesheet">
    @if($rtl)<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">@endif
    <style>{!! \App\View\StoreTheme::css($rtl) !!}</style>

    @viteReactRefresh
    @vite(['resources/js/storefront.jsx'])
    @inertiaHead
    @include('partials.analytics')
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900 antialiased">
    <x-theme-preview-bar />
    @inertia
    @include('partials.live-chat')
    @if(setting('features.ai_chat_enabled') && (setting('ai.api_key') || setting('features.ai_api_key')))
        {{-- The AI chat widget is still Alpine + Font Awesome (React version in S6) --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
        @include('components.ai-chat-widget')
    @endif
</body>
</html>
