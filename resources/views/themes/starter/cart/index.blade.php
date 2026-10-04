@extends('layouts.app')

@section('title', __('messages.your_cart'))

{{--
    Starter theme cart page: single column. Cart lines first, then the order summary as a plain bordered box
    on the right of the actions row, then "you may also like". Square corners, outlines instead of shadows.
    Reuses the shared blocks in cart/blocks/*.
--}}
@section('content')
<style>
    .cart-page--starter .rounded, .cart-page--starter .rounded-lg, .cart-page--starter .rounded-xl { border-radius: 0 !important; }
    .cart-page--starter .shadow-sm { box-shadow: none !important; border: 1px solid #d1d5db; }
    .cart-page--starter .cart-summary .sticky { position: static !important; }
    .cart-page--starter a.bg-primary-600, .cart-page--starter button[type="submit"].bg-gray-900 { text-transform: uppercase; letter-spacing: .05em; }
</style>

<div class="cart-page cart-page--starter max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 class="text-2xl md:text-3xl font-bold mb-6 pb-3 border-b-2" style="border-color: var(--color-primary, #059669);">{{ __('messages.your_cart') }}</h1>

    @if($cart->items->count() > 0)
        @include('cart.blocks.items')

        <div class="grid md:grid-cols-2 gap-8 mt-2 items-start">
            <div>@include('cart.blocks.actions')</div>
            <div class="cart-summary mt-4">@include('cart.blocks.summary')</div>
        </div>

        @include('cart.blocks.cross-sell')
    @else
    @include('cart.blocks.empty')
    @endif
</div>
@endsection
