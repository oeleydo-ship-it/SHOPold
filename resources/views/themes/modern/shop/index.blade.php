@extends('layouts.app')

@section('title', __('messages.shop'))

@include('shop.listing.styles')

{{--
    Modern theme shop page: the title banner becomes a rounded gradient card inside the page width,
    filters and sort bar sit in soft raised cards with pill controls. Reuses the shared blocks in shop/listing/*.
--}}
@section('content')
<style>
    .shop-page--modern .shop-hero { background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary, #7c3aed) 14%, #ffffff), color-mix(in srgb, var(--color-accent, #f472b6) 14%, #ffffff)) !important; border: 0 !important; border-radius: 1.75rem; overflow: hidden; }
    .shop-page--modern .rounded-2xl { border-radius: 1.5rem !important; }
    .shop-page--modern .rounded-lg, .shop-page--modern .rounded-xl { border-radius: 1rem !important; }
    .shop-page--modern select, .shop-page--modern input[type="text"], .shop-page--modern input[type="number"] { border-radius: 9999px !important; }
    .shop-page--modern button[type="submit"], .shop-page--modern a.bg-primary-600 { border-radius: 9999px !important; }
    .shop-page--modern .shadow-sm { box-shadow: 0 12px 40px -20px rgba(17, 24, 39, .25) !important; }
    .shop-page--modern aside .sticky { top: 7rem; }
</style>

<x-shop-state class="shop-page shop-page--modern">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        @include('shop.listing.hero')
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
        @include('shop.listing.active-filters')

        @include('shop.listing.mobile-filters')

        <div class="flex flex-col lg:flex-row gap-8">
            @include('shop.listing.sidebar')

            <div class="flex-1 min-w-0">
                @include('shop.listing.sort-bar')

                @include('shop.listing.products')
            </div>
        </div>
    </div>
</x-shop-state>
@endsection
