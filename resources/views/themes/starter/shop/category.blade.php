@extends('layouts.app')

@section('title', $category->translated_name)

{{--
    Starter theme category page: plain title with a primary rule underneath and the product count,
    then the shared product grid with square corners and outlines. Reuses shop/category-page/*.
--}}
@section('content')
<style>
    .category-page--starter .rounded, .category-page--starter .rounded-lg, .category-page--starter .rounded-xl,
    .category-page--starter .rounded-2xl { border-radius: 0 !important; }
    .category-page--starter .shadow-sm { box-shadow: none !important; border: 1px solid #d1d5db; }
    .category-page--starter a.bg-primary-600 { text-transform: uppercase; letter-spacing: .05em; }
</style>

<div class="category-page category-page--starter max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @include('shop.category-page.breadcrumb')

    <header class="mb-8 pb-4 border-b-2" style="border-color: var(--color-primary, #059669);">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $category->translated_name }}</h1>
            <span class="text-sm text-gray-500">{{ trans_choice(':count product|:count products', $products->total(), ['count' => $products->total()]) }}</span>
        </div>
        @if($category->translated_description)
            <p class="mt-2 text-gray-600 max-w-3xl">{{ $category->translated_description }}</p>
        @endif
    </header>

    @include('shop.category-page.products')
</div>
@endsection
