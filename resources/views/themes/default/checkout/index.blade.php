@extends('layouts.app')

@section('title', 'Checkout')

{{--
    Default theme checkout: same layout as the shared page. Rearrange or restyle the blocks
    (checkout/blocks/*.blade.php) here to change the default theme only.
    Every layout must keep: the x-data="checkoutForm()" root, the <form> with x-ref="checkoutForm" and @csrf
    around shipping-info / delivery / payment / notes / summary, and the scripts block after the form.
--}}
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="checkoutForm()">
    <h1 class="text-3xl font-bold mb-8">Checkout</h1>

    @include('checkout.blocks.guest')

    <form action="{{ route('checkout.process') }}" method="POST" x-ref="checkoutForm">
        @csrf
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Shipping Info -->
            <div class="lg:col-span-2 space-y-6">
                @include('checkout.blocks.shipping-info')

                @include('checkout.blocks.delivery')

                @include('checkout.blocks.payment')

                @include('checkout.blocks.notes')
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                @include('checkout.blocks.summary')
            </div>
        </div>
    </form>
</div>

@include('checkout.blocks.scripts')
@endsection
