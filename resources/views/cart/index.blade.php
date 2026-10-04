@extends('layouts.app')

@section('title', __('messages.your_cart'))

{{--
    Cart page layout. The blocks live in cart/blocks/*.blade.php and are shared by every theme;
    a theme rearranges or restyles them with its own themes/<slug>/cart/index.blade.php.
--}}
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold mb-8">{{ __('messages.your_cart') }}</h1>

    @if($cart->items->count() > 0)
    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2">
            @include('cart.blocks.items')

            @include('cart.blocks.actions')

            @include('cart.blocks.cross-sell')
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-1">
            @include('cart.blocks.summary')
        </div>
    </div>
    @else
    @include('cart.blocks.empty')
    @endif
</div>
@endsection
