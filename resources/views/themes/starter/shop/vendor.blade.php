@extends('layouts.app')

@section('title', $vendor->store_name)
@if($vendor->description)
    @section('meta_description', \Illuminate\Support\Str::limit($vendor->description, 155))
@endif

{{--
    Starter theme vendor store page: no hero image, a plain header with the logo, name and a primary rule,
    square corners and outlines. Reuses shop/vendor-page/*.
--}}
@section('content')
<style>
    .vendor-page--starter .rounded, .vendor-page--starter .rounded-lg, .vendor-page--starter .rounded-xl,
    .vendor-page--starter .rounded-2xl { border-radius: 0 !important; }
    .vendor-page--starter .shadow-sm, .vendor-page--starter .shadow-lg { box-shadow: none !important; border: 1px solid #d1d5db; }
    .vendor-page--starter a.bg-primary-600 { text-transform: uppercase; letter-spacing: .05em; }
</style>

<div class="vendor-page vendor-page--starter max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <header class="mb-8 pb-6 border-b-2 flex flex-col sm:flex-row sm:items-center gap-4" style="border-color: var(--color-primary, #059669);">
        @include('shop.vendor-page.logo', ['size' => 'w-20 h-20'])
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">{{ $vendor->store_name }}</h1>
            @include('shop.vendor-page.meta', ['class' => 'mt-1 text-gray-500'])
            @if($vendor->description)
                <p class="mt-2 text-gray-600 max-w-3xl">{{ \Illuminate\Support\Str::limit($vendor->description, 300) }}</p>
            @endif
        </div>
        @include('shop.vendor-page.social', ['class' => 'w-9 h-9 flex items-center justify-center border border-gray-300 text-gray-600 hover:text-gray-900 transition'])
    </header>

    @include('shop.vendor-page.products')
</div>
@endsection
