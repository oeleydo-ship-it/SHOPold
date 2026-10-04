@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title)

@section('meta')
<meta name="description" content="{{ $post->meta_description ?: $post->excerpt }}">
@endsection

{{--
    Default theme blog post: same as the shared layout; rearrange or restyle the blocks
    (blog/post/*.blade.php) here to change the default theme only.
--}}
@section('content')
<article class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            @include('blog.post.breadcrumb')

            @include('blog.post.header')

            @include('blog.post.image')

            @include('blog.post.content')

            @include('blog.post.author')

            @include('blog.post.related')

            @include('blog.post.comments')
        </div>
    </div>
</article>
@endsection
