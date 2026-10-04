@extends('layouts.app')

@section('title', __('messages.shop'))

@include('shop.listing.styles')

{{--
    Shop page layout. The blocks live in shop/listing/*.blade.php and are shared by every theme;
    a theme rearranges or restyles them with its own themes/<slug>/shop/index.blade.php.
--}}
@section('content')
<x-shop-state>
    @include('shop.listing.hero')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
        @include('shop.listing.active-filters')

        @include('shop.listing.mobile-filters')

        <div class="flex flex-col lg:flex-row gap-8">
            @include('shop.listing.sidebar')

            <!-- Products Grid -->
            <div class="flex-1 min-w-0">
                @include('shop.listing.sort-bar')

                @include('shop.listing.products')
            </div>
        </div>
    </div>
</x-shop-state>
@endsection
