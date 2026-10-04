@extends('layouts.app')

@section('title', $category->translated_name)

{{--
    Default theme category page: same layout as the shared page. Rearrange or restyle the blocks
    (shop/category-page/*.blade.php) here to change the default theme only.
--}}
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @include('shop.category-page.breadcrumb')

    @include('shop.category-page.banner')

    @include('shop.category-page.products')
</div>
@endsection
