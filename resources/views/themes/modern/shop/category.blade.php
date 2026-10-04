@extends('layouts.app')

@section('title', $category->translated_name)

{{--
    Modern theme category page: large rounded hero with the category image (or a primary-to-accent
    gradient), product count, pills to jump to other categories, then a roomy grid.
    The product grid itself is the shared block shop/category-page/products.
--}}
@section('content')
<style>
    .category-page--modern .rounded-xl, .category-page--modern .rounded-2xl { border-radius: 1.5rem !important; }
    .category-page--modern a.bg-primary-600 { border-radius: 9999px !important; }
</style>

<div class="category-page category-page--modern max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    @include('shop.category-page.breadcrumb')

    <section class="relative overflow-hidden rounded-3xl mb-6 text-white shadow-xl"
        style="background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));">
        @if($category->image)
            <img src="{{ asset('storage/' . $category->image) }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30 mix-blend-overlay">
        @endif
        <div class="relative px-6 py-10 md:px-12 md:py-16">
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 backdrop-blur text-xs font-semibold uppercase tracking-widest">
                {{ trans_choice(':count product|:count products', $products->total(), ['count' => $products->total()]) }}
            </span>
            <h1 class="mt-3 text-3xl md:text-5xl font-extrabold tracking-tight">{{ $category->translated_name }}</h1>
            @if($category->translated_description)
                <p class="mt-3 max-w-2xl text-white/85 md:text-lg">{{ $category->translated_description }}</p>
            @endif
        </div>
    </section>

    @include('shop.category-page.other-categories')

    @include('shop.category-page.products')
</div>
@endsection
