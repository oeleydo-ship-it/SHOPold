@extends('layouts.app')

@section('title', isset($category) ? $category->name . ' - Blog' : 'Blog')

{{--
    Starter theme blog list: one plain column. Title with a primary rule, search, posts, then categories and
    newsletter at the bottom. Square corners, outlines instead of shadows. Reuses blog/blocks/*.
--}}
@section('content')
<style>
    .blog-page--starter .rounded, .blog-page--starter .rounded-lg, .blog-page--starter .rounded-xl,
    .blog-page--starter .rounded-full, .blog-page--starter input { border-radius: 0 !important; }
    .blog-page--starter .shadow-sm, .blog-page--starter .shadow-lg, .blog-page--starter .hover\:shadow-lg:hover { box-shadow: none !important; }
    .blog-page--starter article, .blog-page--starter .bg-white.shadow-sm { border: 1px solid #d1d5db; }
    .blog-page--starter .blog-intro > div { text-align: left !important; margin-bottom: 1.5rem !important; padding-bottom: 1rem; border-bottom: 2px solid var(--color-primary, #059669); }
    .blog-page--starter .blog-intro p { margin-left: 0 !important; }
    .blog-page--starter .blog-side > aside { display: contents; }
</style>

<div class="blog-page blog-page--starter bg-white py-8">
    <div class="max-w-3xl mx-auto px-4">
        <div class="blog-intro">@include('blog.blocks.header')</div>

        @include('blog.blocks.search')

        @include('blog.blocks.posts')

        <div class="blog-side mt-10 grid md:grid-cols-2 gap-6">
            @include('blog.blocks.sidebar')
        </div>
    </div>
</div>
@endsection
