@extends('layouts.app')

@section('title', __('messages.shop'))

@include('shop.listing.styles')

{{--
    Starter theme shop page: plain catalogue. Flat title band, filters in a bordered column on the right,
    square corners and outlines instead of shadows. Reuses the shared blocks in shop/listing/*.
--}}
@section('content')
<style>
    .shop-page--starter .shop-hero { background: #ffffff !important; border-bottom: 2px solid var(--color-primary, #059669) !important; }
    .shop-page--starter .rounded, .shop-page--starter .rounded-md, .shop-page--starter .rounded-lg,
    .shop-page--starter .rounded-xl, .shop-page--starter .rounded-2xl, .shop-page--starter .rounded-full { border-radius: 0 !important; }
    .shop-page--starter .shadow-sm, .shop-page--starter .shadow, .shop-page--starter .shadow-lg { box-shadow: none !important; }
    .shop-page--starter .bg-white.shadow-sm, .shop-page--starter .bg-white.border-gray-100 { border-color: #d1d5db !important; }
    .shop-page--starter button[type="submit"] { text-transform: uppercase; letter-spacing: .05em; }
</style>

<x-shop-state class="shop-page shop-page--starter">
    @include('shop.listing.hero')

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @include('shop.listing.active-filters')

        @include('shop.listing.mobile-filters')

        {{-- filters on the right-hand side --}}
        <div class="flex flex-col lg:flex-row-reverse gap-8">
            @include('shop.listing.sidebar')

            <div class="flex-1 min-w-0">
                @include('shop.listing.sort-bar')

                @include('shop.listing.products')
            </div>
        </div>
    </div>
</x-shop-state>
@endsection
