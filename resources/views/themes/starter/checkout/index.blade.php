@extends('layouts.app')

@section('title', 'Checkout')

{{--
    Starter theme checkout: one plain column. Details, delivery, payment and notes stacked as bordered
    sections, the order summary at the bottom right before placing the order. Square corners, no shadows.
    Same form, fields and scripts as the shared page: only the arrangement and styling differ.
--}}
@section('content')
<style>
    .checkout-page--starter .rounded, .checkout-page--starter .rounded-lg, .checkout-page--starter .rounded-xl,
    .checkout-page--starter input, .checkout-page--starter select, .checkout-page--starter textarea { border-radius: 0 !important; }
    .checkout-page--starter .shadow-sm { box-shadow: none !important; border: 1px solid #d1d5db; }
    .checkout-page--starter .checkout-summary .sticky { position: static !important; }
    .checkout-page--starter button[type="submit"] { text-transform: uppercase; letter-spacing: .05em; }
</style>

<div class="checkout-page checkout-page--starter max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="checkoutForm()">
    <h1 class="text-2xl md:text-3xl font-bold mb-6 pb-3 border-b-2" style="border-color: var(--color-primary, #059669);">Checkout</h1>

    @include('checkout.blocks.guest')

    <form action="{{ route('checkout.process') }}" method="POST" x-ref="checkoutForm">
        @csrf
        <div class="space-y-6">
            @include('checkout.blocks.shipping-info')

            @include('checkout.blocks.delivery')

            @include('checkout.blocks.payment')

            @include('checkout.blocks.notes')

            <div class="checkout-summary">
                @include('checkout.blocks.summary')
            </div>
        </div>
    </form>
</div>

@include('checkout.blocks.scripts')
@endsection
