@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)

@section('meta')
<meta name="description" content="{{ $post->meta_description ?: $post->excerpt }}">
@endsection

{{--
    Modern theme blog post: featured image first as a wide rounded banner, then the title block,
    the article in a soft rounded card, author, related posts and comments. Reuses blog/post/*.
--}}
@section('content')
<style>
    .blog-post--modern .rounded-2xl { border-radius: 1.75rem !important; }
    .blog-post--modern .shadow-sm { box-shadow: 0 12px 40px -20px rgba(17, 24, 39, .25) !important; }
    .blog-post--modern header h1 { font-weight: 800; letter-spacing: -0.02em; }
    .blog-post--modern button[type="submit"], .blog-post--modern a.bg-primary-600 { border-radius: 9999px !important; }
</style>

<article class="blog-post blog-post--modern py-10">
    <div class="container mx-auto px-4">
        <div class="max-w-5xl mx-auto">
            @include('blog.post.breadcrumb')

            @include('blog.post.image')

            <div class="max-w-4xl mx-auto">
                @include('blog.post.header')

                @include('blog.post.content')

                @include('blog.post.author')
            </div>

            @include('blog.post.related')

            <div class="max-w-4xl mx-auto">
                @include('blog.post.comments')
            </div>
        </div>
    </div>
</article>
@endsection
