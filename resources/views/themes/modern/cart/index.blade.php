@extends('layouts.app')

@section('title', __('messages.your_cart'))

{{--
    Modern theme cart page: headline with item count, cart lines in a soft rounded card with pill controls,
    and a raised gradient-edged order summary that stays in view. Reuses the shared blocks in cart/blocks/*.
--}}
@section('content')
<style>
    .cart-page--modern .rounded-lg { border-radius: 1rem !important; }
    .cart-page--modern .rounded-xl { border-radius: 1.5rem !important; }
    .cart-page--modern select, .cart-page--modern input[name="coupon_code"],
    .cart-page--modern button[type="submit"].bg-gray-900, .cart-page--modern button[type="submit"].bg-primary-600,
    .cart-page--modern a.bg-primary-600 { border-radius: 9999px !important; }
    .cart-page--modern .shadow-sm { box-shadow: 0 12px 40px -20px rgba(17, 24, 39, .25) !important; }
    .cart-page--modern .cart-summary a.bg-primary-600 {
        background: linear-gradient(90deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6)) !important;
        box-shadow: 0 10px 25px -10px var(--color-primary, #7c3aed);
    }
</style>

<div class="cart-page cart-page--modern max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex items-end justify-between gap-4 mb-8">
        <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">{{ __('messages.your_cart') }}</h1>
        @if($cart->items->count() > 0)
            <span class="px-3 py-1 rounded-full text-sm font-semibold text-primary-700 bg-primary-100">
                {{ trans_choice(':count item|:count items', $cart->items->sum('quantity'), ['count' => $cart->items->sum('quantity')]) }}
            </span>
        @endif
    </div>

    @if($cart->items->count() > 0)
    <div class="grid lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-8">
            @include('cart.blocks.items')

            @include('cart.blocks.actions')

            @include('cart.blocks.cross-sell')
        </div>

        <div class="lg:col-span-4 lg:sticky lg:top-28">
            <div class="cart-summary rounded-3xl p-[2px]" style="background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));">
                @include('cart.blocks.summary')
            </div>
        </div>
    </div>
    @else
    @include('cart.blocks.empty')
    @endif
</div>
@endsection
