@extends('layouts.app')

@section('title', 'Checkout')

{{--
    Modern theme checkout: a numbered step rail (details, delivery, payment, notes) with each step in a soft
    rounded card, and a gradient-edged order summary that stays in view. Same form, fields and scripts as the
    shared page: only the arrangement and styling differ.
--}}
@section('content')
<style>
    .checkout-page--modern .rounded-lg { border-radius: 1rem !important; }
    .checkout-page--modern .rounded-xl { border-radius: 1.5rem !important; }
    .checkout-page--modern .shadow-sm { box-shadow: 0 12px 40px -20px rgba(17, 24, 39, .25) !important; }
    .checkout-page--modern input:not([type="radio"]):not([type="checkbox"]), .checkout-page--modern select, .checkout-page--modern textarea { border-radius: .9rem !important; }
    .checkout-page--modern .checkout-steps > .checkout-step { position: relative; padding-left: 3.25rem; }
    .checkout-page--modern .checkout-steps > .checkout-step::before {
        counter-increment: step; content: counter(step);
        position: absolute; left: 0; top: 1.25rem; width: 2.25rem; height: 2.25rem; border-radius: 9999px;
        display: flex; align-items: center; justify-content: center; font-weight: 800; color: #fff;
        background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));
    }
    .checkout-page--modern .checkout-steps { counter-reset: step; }
    .checkout-page--modern .checkout-summary button[type="submit"] {
        border-radius: 9999px !important;
        background: linear-gradient(90deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6)) !important;
    }
</style>

<div class="checkout-page checkout-page--modern max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="checkoutForm()">
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">Checkout</h1>
        <p class="text-gray-500 mt-1">Four quick steps and your order is on its way.</p>
    </div>

    @include('checkout.blocks.guest')

    <form action="{{ route('checkout.process') }}" method="POST" x-ref="checkoutForm">
        @csrf
        <div class="grid lg:grid-cols-12 gap-8 items-start">
            <div class="lg:col-span-8 space-y-6 checkout-steps">
                <div class="checkout-step">@include('checkout.blocks.shipping-info')</div>
                <div class="checkout-step">@include('checkout.blocks.delivery')</div>
                <div class="checkout-step">@include('checkout.blocks.payment')</div>
                <div class="checkout-step">@include('checkout.blocks.notes')</div>
            </div>

            <div class="lg:col-span-4 lg:sticky lg:top-28">
                <div class="checkout-summary rounded-3xl p-[2px]" style="background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));">
                    @include('checkout.blocks.summary')
                </div>
            </div>
        </div>
    </form>
</div>

@include('checkout.blocks.scripts')
@endsection
