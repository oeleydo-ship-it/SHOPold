@extends('layouts.app')

@section('title', $product->translated_name)

@section('meta_description', $product->translated_short_description ?? Str::limit(strip_tags(clean_html($product->translated_description)), 160))

{{--
    Modern theme product page: wide sticky gallery on a tinted panel, buy box in its own raised card,
    pill-shaped controls, details and reviews in rounded cards. Reuses the shared blocks in shop/product/*.
--}}
@section('content')
<style>
    .product-page--modern .rounded-lg { border-radius: 1rem !important; }
    .product-page--modern .rounded-xl, .product-page--modern .rounded-2xl { border-radius: 1.5rem !important; }
    .product-page--modern button.bg-primary-600, .product-page--modern a.bg-primary-600,
    .product-page--modern button[type="submit"] { border-radius: 9999px !important; }
    .product-page--modern .shadow-sm { box-shadow: 0 10px 40px -18px rgba(17, 24, 39, .25) !important; }
    .product-page--modern h1 { letter-spacing: -0.02em; }
</style>

<div class="product-page product-page--modern max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @include('shop.product.breadcrumb')

    <div class="grid lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-7 lg:sticky lg:top-28">
            <div class="rounded-3xl p-4 sm:p-6" style="background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary, #7c3aed) 8%, #ffffff), #ffffff);">
                @include('shop.product.gallery')
            </div>
        </div>

        <div class="lg:col-span-5">
            <div class="bg-white rounded-3xl shadow-xl ring-1 ring-gray-100 p-6 lg:p-8">
                @include('shop.product.info')
            </div>
        </div>
    </div>

    <div class="mt-10 bg-white rounded-3xl shadow-sm ring-1 ring-gray-100 overflow-hidden">
        @include('shop.product.tabs')
    </div>

    @include('shop.product.reviews')

    @include('shop.product.cross-sell')

    @include('shop.product.related')
</div>

@include('shop.product.mobile-bar')
@endsection
