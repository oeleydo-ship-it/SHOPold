@extends('layouts.app')

@section('title', $vendor->store_name)
@if($vendor->description)
    @section('meta_description', \Illuminate\Support\Str::limit($vendor->description, 155))
@endif

{{--
    Default theme vendor store page: same layout as the shared page. Rearrange or restyle the blocks
    (shop/vendor-page/*.blade.php) here to change the default theme only.
--}}
@section('content')
    @include('shop.vendor-page.banner')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Products</h2>
        @include('shop.vendor-page.products')
    </div>
@endsection
