@extends('layouts.app')

@section('title', 'Order Confirmed')

{{-- Order confirmation layout; blocks in checkout/blocks/success-*.blade.php. Themes override themes/<slug>/checkout/success.blade.php. --}}
@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="bg-white rounded-2xl shadow-sm p-8 text-center">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-check text-4xl text-green-500"></i>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Thank You!</h1>
        <p class="text-xl text-gray-600 mb-6">Your order has been placed successfully</p>
        
        @include('checkout.blocks.success-details')

        <p class="text-gray-600 mb-8">
            A confirmation email has been sent to <strong>{{ $order->shipping_email }}</strong>
        </p>

        @include('checkout.blocks.success-actions')
    </div>
</div>
@endsection
