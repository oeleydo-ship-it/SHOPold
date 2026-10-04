@extends('layouts.app')

@section('title', 'Order Confirmed')

{{-- Modern theme order confirmation: gradient hero with the check mark, order facts in a soft card. Reuses checkout/blocks/success-*. --}}
@section('content')
<style>
    .success-page--modern .rounded-xl { border-radius: 1.5rem !important; }
    .success-page--modern a.rounded-lg { border-radius: 9999px !important; }
</style>
<div class="success-page success-page--modern max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="rounded-3xl overflow-hidden bg-white shadow-xl ring-1 ring-gray-100 text-center">
        <div class="px-8 pt-12 pb-10 text-white" style="background: linear-gradient(135deg, var(--color-primary, #7c3aed), var(--color-accent, #f472b6));">
            <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur flex items-center justify-center mx-auto mb-5 ring-4 ring-white/30">
                <i class="fas fa-check text-4xl"></i>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight">Thank You!</h1>
            <p class="mt-2 text-white/85 text-lg">Your order has been placed successfully</p>
        </div>
        <div class="p-8">
            @include('checkout.blocks.success-details')

            <p class="text-gray-600 mb-8">
                A confirmation email has been sent to <strong>{{ $order->shipping_email }}</strong>
            </p>

            @include('checkout.blocks.success-actions')
        </div>
    </div>
</div>
@endsection
