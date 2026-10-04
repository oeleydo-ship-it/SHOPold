@extends('layouts.app')

@section('title', 'Order Confirmed')

{{-- Starter theme order confirmation: plain receipt with a primary rule, square corners. Reuses checkout/blocks/success-*. --}}
@section('content')
<style>
    .success-page--starter .rounded-xl, .success-page--starter .rounded-lg { border-radius: 0 !important; }
    .success-page--starter .bg-gray-50 { background: #fff !important; border: 1px solid #d1d5db; }
    .success-page--starter a { text-transform: uppercase; letter-spacing: .05em; }
</style>
<div class="success-page success-page--starter max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="border-t-4 bg-white p-8" style="border-color: var(--color-primary, #059669);">
        <p class="text-sm font-semibold uppercase tracking-wider text-primary-600"><i class="fas fa-check mr-1"></i>Order confirmed</p>
        <h1 class="text-2xl font-bold text-gray-900 mt-1 mb-1">Thank You!</h1>
        <p class="text-gray-600 mb-6">Your order has been placed successfully</p>

        @include('checkout.blocks.success-details')

        <p class="text-gray-600 mb-8">
            A confirmation email has been sent to <strong>{{ $order->shipping_email }}</strong>
        </p>

        @include('checkout.blocks.success-actions')
    </div>
</div>
@endsection
