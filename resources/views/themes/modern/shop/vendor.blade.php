@extends('layouts.app')

@section('title', $vendor->store_name)
@if($vendor->description)
    @section('meta_description', \Illuminate\Support\Str::limit($vendor->description, 155))
@endif

{{--
    Modern theme vendor store page: a rounded gradient hero (primary to accent, with the banner blended in),
    the logo overlapping its lower edge, a full "about" text and pill-shaped facts. Reuses shop/vendor-page/*.
--}}
@section('content')
<style>
    .vendor-page--modern .rounded-xl, .vendor-page--modern .rounded-2xl { border-radius: 1.5rem !important; }
    .vendor-page--modern a.bg-primary-600 { border-radius: 9999px !important; }
</style>

<div class="vendor-page vendor-page--modern max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <section class="relative overflow-hidden rounded-3xl text-white shadow-xl"
        style="background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));">
        @if($vendor->banner)
            <img src="{{ asset('storage/' . $vendor->banner) }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30 mix-blend-overlay">
        @endif
        <div class="relative px-6 pt-10 pb-16 md:px-12 md:pt-14 md:pb-20 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 backdrop-blur text-xs font-semibold uppercase tracking-widest">Independent seller</span>
                <h1 class="mt-3 text-3xl md:text-5xl font-extrabold tracking-tight">{{ $vendor->store_name }}</h1>
            </div>
            @include('shop.vendor-page.social', ['class' => 'w-10 h-10 rounded-full flex items-center justify-center bg-white/20 backdrop-blur text-white hover:bg-white/30 transition'])
        </div>
    </section>

    <div class="relative -mt-12 px-6 md:px-12 flex flex-col md:flex-row md:items-end gap-4 md:gap-6 mb-8">
        @include('shop.vendor-page.logo', ['size' => 'w-24 h-24 md:w-28 md:h-28 ring-4 ring-white'])
        @include('shop.vendor-page.meta', ['class' => 'text-gray-600 [&>span]:bg-white [&>span]:shadow-sm [&>span]:rounded-full [&>span]:px-3 [&>span]:py-1'])
    </div>

    @if($vendor->description)
        <p class="mb-8 max-w-3xl text-gray-600 md:text-lg whitespace-pre-line">{{ $vendor->description }}</p>
    @endif

    @include('shop.vendor-page.products')
</div>
@endsection
