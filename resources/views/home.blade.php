@extends('layouts.app')

@section('title', 'Home')

@section('content')
@php
    // Styling Settings
    $headingFont = $appSettings['homepage']['heading_font'] ?? '';
    $bodyFont = $appSettings['homepage']['font_family'] ?? '';
    $heroBgColor = $appSettings['homepage']['hero_bg_color'] ?? '';
    $featuresBgColor = $appSettings['homepage']['features_bg_color'] ?? '';
    $categoriesBgColor = $appSettings['homepage']['categories_bg_color'] ?? '';
    $featuredBgColor = $appSettings['homepage']['featured_bg_color'] ?? '';
    $promoBgColor = $appSettings['homepage']['promo_bg_color'] ?? '';
    $arrivalsBgColor = $appSettings['homepage']['arrivals_bg_color'] ?? '';
    $newsletterBgColor = $appSettings['homepage']['newsletter_bg_color'] ?? '';
    
    // Icon mapping
    $iconMap = [
        'star' => 'fa-star',
        'fire' => 'fa-fire',
        'sparkles' => 'fa-magic',
        'gift' => 'fa-gift',
        'tag' => 'fa-tag',
        'truck' => 'fa-truck',
        'undo' => 'fa-undo',
        'shield-alt' => 'fa-shield-alt',
        'headset' => 'fa-headset',
        'clock' => 'fa-clock',
        'credit-card' => 'fa-credit-card',
        'heart' => 'fa-heart',
    ];
    
    // Section order - get from settings or use default
    $allAvailableSections = ['hero', 'features', 'categories', 'featured', 'flash_deals', 'top_rated', 'two_column_banners', 'promo', 'arrivals', 'blog', 'newsletter'];
    $storedSectionOrder = $appSettings['homepage']['section_order'] ?? '';
    $sections = $storedSectionOrder ? explode(',', $storedSectionOrder) : $allAvailableSections;
    
    // Add any missing sections to the end
    foreach ($allAvailableSections as $section) {
        if (!in_array($section, $sections)) {
            $sections[] = $section;
        }
    }
    
    // Clean up section names (trim whitespace)
    $sections = array_map('trim', $sections);
@endphp

@if($headingFont || $bodyFont)
<style>
    @if($headingFont)
    @import url('https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $headingFont) }}:wght@400;500;600;700&display=swap');
    .homepage-heading { font-family: '{{ $headingFont }}', serif !important; }
    @endif
    @if($bodyFont)
    @import url('https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $bodyFont) }}:wght@300;400;500;600&display=swap');
    .homepage-body { font-family: '{{ $bodyFont }}', sans-serif !important; }
    @endif
</style>
@endif

@include('home.partials.skin')

{{-- Render sections based on order (themes style them through home/partials/skin and can replace any home/sections/* file) --}}
<div class="home-page">
@foreach($sections as $section)
    @switch(trim($section))
        @case('hero')
            @include('home.sections.hero')
            @break
        @case('features')
            @include('home.sections.features')
            @break
        @case('categories')
            @include('home.sections.categories')
            @break
        @case('featured')
            @include('home.sections.featured')
            @break
        @case('flash_deals')
            @include('home.sections.flash_deals')
            @break
        @case('top_rated')
            @include('home.sections.top_rated')
            @break
        @case('two_column_banners')
            @include('home.sections.two_column_banners')
            @break
        @case('promo')
            @include('home.sections.promo')
            @break
        @case('arrivals')
            @include('home.sections.arrivals')
            @break
        @case('blog')
            @include('home.sections.blog')
            @break
        @case('newsletter')
            @include('home.sections.newsletter')
            @break
    @endswitch
@endforeach
</div>

@endsection
