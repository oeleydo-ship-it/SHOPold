@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)

@section('meta')
<meta name="description" content="{{ $post->meta_description ?: $post->excerpt }}">
@endsection

{{--
    Starter theme blog post: a plain, narrow reading column on white. Title first, then the image and the
    article without a card, author and comments, related posts last. Reuses blog/post/*.
--}}
@section('content')
<style>
    .blog-post--starter .rounded, .blog-post--starter .rounded-lg, .blog-post--starter .rounded-xl,
    .blog-post--starter .rounded-2xl, .blog-post--starter .rounded-full, .blog-post--starter input,
    .blog-post--starter textarea { border-radius: 0 !important; }
    .blog-post--starter .shadow-sm, .blog-post--starter .shadow-lg { box-shadow: none !important; }
    .blog-post--starter .bg-white.shadow-sm { padding-left: 0 !important; padding-right: 0 !important; }
    .blog-post--starter header { padding-bottom: 1rem; border-bottom: 2px solid var(--color-primary, #059669); }
</style>

<article class="blog-post blog-post--starter bg-white py-8">
    <div class="max-w-2xl mx-auto px-4">
        @include('blog.post.breadcrumb')

        @include('blog.post.header')

        @include('blog.post.image')

        @include('blog.post.content')

        @include('blog.post.author')

        @include('blog.post.comments')

        @include('blog.post.related')
    </div>
</article>
@endsection
