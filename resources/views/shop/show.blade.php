@extends('layouts.app')

@section('title', $product->translated_name)

@section('meta_description', $product->translated_short_description ?? Str::limit(strip_tags(clean_html($product->translated_description)), 160))

{{--
    Product page layout. The blocks live in shop/product/*.blade.php and are shared by every theme;
    a theme rearranges or restyles them by providing its own themes/<slug>/shop/show.blade.php.
--}}
@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @include('shop.product.breadcrumb')

    <!-- Product Details -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="grid md:grid-cols-2 gap-8 p-6 lg:p-8">
            @include('shop.product.gallery')

            @include('shop.product.info')
        </div>

        @include('shop.product.tabs')
    </div>

    @include('shop.product.reviews')

    @include('shop.product.cross-sell')

    @include('shop.product.related')
</div>

@include('shop.product.mobile-bar')
@endsection
