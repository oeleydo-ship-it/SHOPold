@extends('layouts.app')

@section('title', $vendor->store_name)
@if($vendor->description)
    @section('meta_description', \Illuminate\Support\Str::limit($vendor->description, 155))
@endif

{{--
    Vendor store page, shared by every theme. Built from shop/vendor-page/* blocks; a theme rearranges them in
    themes/<slug>/shop/vendor.blade.php or restyles one block in themes/<slug>/shop/vendor-page/<block>.blade.php.
--}}
@section('content')
    @include('shop.vendor-page.banner')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Products</h2>
        </div>
        @include('shop.vendor-page.products')
    </div>
@endsection
