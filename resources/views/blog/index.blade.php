@extends('layouts.app')

@section('title', isset($category) ? $category->name . ' - Blog' : 'Blog')

{{--
    Blog list layout. Blocks live in blog/blocks/*.blade.php and are shared by every theme;
    a theme rearranges or restyles them with its own themes/<slug>/blog/index.blade.php.
--}}
@section('content')
<div class="bg-gray-50 py-12">
    <div class="container mx-auto px-4">
        @include('blog.blocks.header')

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Content -->
            <div class="lg:w-2/3">
                @include('blog.blocks.search')

                @include('blog.blocks.posts')
            </div>

            @include('blog.blocks.sidebar')
        </div>
    </div>
</div>
@endsection
