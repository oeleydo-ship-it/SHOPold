@extends('layouts.app')

@section('title', __('messages.your_cart'))

{{--
    Default theme cart page: same layout as the shared page. Rearrange or restyle the blocks
    (cart/blocks/*.blade.php) here to change the default theme only.
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
