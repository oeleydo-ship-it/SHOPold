@extends('layouts.app')

@section('title', isset($category) ? $category->name . ' - Blog' : 'Blog')

{{--
    Default theme blog list: same as the shared layout; rearrange or restyle the blocks
    (blog/blocks/*.blade.php) here to change the default theme only.
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
