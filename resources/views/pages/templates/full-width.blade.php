@extends('layouts.app')

@section('title', (string) ($page->getTranslatedAttribute('meta_title') ?? $page->translated_title ?? ''))
@section('meta_description', (string) ($page->getTranslatedAttribute('meta_description') ?? $page->excerpt ?? '')) {{-- never null: a null value would open a block section --}}

@section('content')
@include('pages.partials.skin')
<div class="content-page">
@if($page->featured_image)
<div class="relative h-96 overflow-hidden">
    <img src="{{ $page->featured_image_url }}" alt="{{ $page->translated_title }}" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
        <h1 class="text-4xl md:text-5xl font-bold text-white text-center">{{ $page->translated_title }}</h1>
    </div>
</div>
@endif

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    @if(!$page->featured_image)
    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-8 text-center">{{ $page->translated_title }}</h1>
    @endif

    @if($page->excerpt)
    <p class="text-xl text-gray-600 mb-8 text-center max-w-3xl mx-auto">{{ $page->excerpt }}</p>
    @endif

    <div class="prose prose-lg max-w-none">
        {!! safe_page_html($page->translated_content ?? $page->content) !!}
    </div>
</div>
</div>
@endsection
