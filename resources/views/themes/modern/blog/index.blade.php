@extends('layouts.app')

@section('title', isset($category) ? $category->name . ' - Blog' : 'Blog')

{{--
    Modern theme blog list: gradient title card, search in a pill bar, rounded post cards that lift on hover,
    and the categories/newsletter column kept in view while scrolling. Reuses blog/blocks/*.
--}}
@section('content')
<style>
    .blog-page--modern .blog-intro h1 { color: #fff; font-weight: 800; letter-spacing: -0.02em; }
    .blog-page--modern .blog-intro p { color: rgba(255, 255, 255, .85); }
    .blog-page--modern .blog-intro > div { margin-bottom: 0 !important; }
    .blog-page--modern article { border-radius: 1.5rem !important; transition: transform .25s, box-shadow .25s; }
    .blog-page--modern article:hover { transform: translateY(-4px); }
    .blog-page--modern .rounded-xl { border-radius: 1.5rem !important; }
    .blog-page--modern input[name="search"] { border-radius: 9999px !important; }
    .blog-page--modern form button[type="submit"] { border-radius: 9999px !important; }
    .blog-page--modern .blog-side > aside { width: 100% !important; }
</style>

<div class="blog-page blog-page--modern py-10">
    <div class="container mx-auto px-4">
        <div class="blog-intro rounded-3xl px-6 py-12 mb-10 shadow-lg" style="background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));">
            @include('blog.blocks.header')
        </div>

        <div class="flex flex-col lg:flex-row gap-8 items-start">
            <div class="lg:w-2/3">
                @include('blog.blocks.search')

                @include('blog.blocks.posts')
            </div>

            <div class="blog-side lg:w-1/3 lg:sticky lg:top-28">
                @include('blog.blocks.sidebar')
            </div>
        </div>
    </div>
</div>
@endsection
