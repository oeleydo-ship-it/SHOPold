@extends('layouts.app')

@section('title', $product->translated_name)

@section('meta_description', $product->translated_short_description ?? Str::limit(strip_tags(clean_html($product->translated_description)), 160))

{{--
    Starter theme product page: plain catalogue layout. Gallery and buy box side by side with no card,
    square corners and outlines instead of shadows, details below, then related products, reviews last.
    Reuses the shared blocks in shop/product/*.
--}}
@section('content')
<style>
    .product-page--starter .rounded, .product-page--starter .rounded-md, .product-page--starter .rounded-lg,
    .product-page--starter .rounded-xl, .product-page--starter .rounded-2xl { border-radius: 0 !important; }
    .product-page--starter .shadow-sm, .product-page--starter .shadow, .product-page--starter .shadow-md,
    .product-page--starter .shadow-lg { box-shadow: none !important; }
    .product-page--starter .bg-white.shadow-sm { border: 1px solid #d1d5db; }
    .product-page--starter button[type="submit"] { text-transform: uppercase; letter-spacing: .05em; }
</style>

<div class="product-page product-page--starter max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @include('shop.product.breadcrumb')

    <div class="grid md:grid-cols-2 gap-10 pb-10 border-b border-gray-300">
        @include('shop.product.gallery')

        @include('shop.product.info')
    </div>

    <div class="mt-8 border border-gray-300">
        @include('shop.product.tabs')
    </div>

    @include('shop.product.cross-sell')

    @include('shop.product.related')

    @include('shop.product.reviews')
</div>

@include('shop.product.mobile-bar')
@endsection
