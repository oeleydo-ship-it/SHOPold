@extends('layouts.app')

@section('title', $category->translated_name)

{{--
    Category page layout. The blocks live in shop/category-page/*.blade.php and are shared by every theme;
    a theme rearranges or restyles them with its own themes/<slug>/shop/category.blade.php.
--}}
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @include('shop.category-page.breadcrumb')

    @include('shop.category-page.banner')

    @include('shop.category-page.products')
</div>
@endsection
