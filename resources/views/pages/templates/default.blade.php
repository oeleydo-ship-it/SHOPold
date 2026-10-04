@extends('layouts.app')

@section('title', (string) ($page->getTranslatedAttribute('meta_title') ?? $page->translated_title ?? ''))
@section('meta_description', (string) ($page->getTranslatedAttribute('meta_description') ?? $page->excerpt ?? '')) {{-- never null: a null value would open a block section --}}
@section('meta_keywords', $page->meta_keywords ?? '')

@section('content')
@include('pages.partials.skin')
<div class="content-page">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Breadcrumb -->
    <nav class="flex mb-8">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600">{{ __('messages.home') }}</a></li>
            <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
            <li class="text-gray-900 font-medium">{{ $page->translated_title }}</li>
        </ol>
    </nav>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        @if($page->featured_image)
        <div class="h-64 md:h-80 overflow-hidden">
            <img src="{{ $page->featured_image_url }}" alt="{{ $page->translated_title }}" class="w-full h-full object-cover">
        </div>
        @endif

        <div class="p-8 lg:p-12">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">{{ $page->translated_title }}</h1>
            
            @if($page->excerpt)
            <p class="text-xl text-gray-600 mb-8">{{ $page->excerpt }}</p>
            @endif

            <div class="prose prose-lg max-w-none">
                {!! safe_page_html($page->translated_content ?? $page->content) !!}
            </div>
        </div>
    </div>
</div>
</div>
@endsection
