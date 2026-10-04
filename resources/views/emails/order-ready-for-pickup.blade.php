@extends('emails.layouts.branded')
@php($loc = $order->pickupLocation)

@section('title', 'Order ' . $order->order_number . ' ready for pickup')
@section('preheader', 'Your order ' . $order->order_number . ' is ready to collect.')
@section('heading', 'Your order is ready for pickup!')

@section('content')
    <p style="margin:0 0 12px;">Hi {{ $order->shipping_name ?? $order->user?->name ?? 'there' }},</p>
    <p style="margin:0 0 12px;">Your order <strong>#{{ $order->order_number }}</strong> is ready to collect.</p>

    @if($loc)
        @include('emails.partials.box', ['title' => 'Pickup location', 'lines' => [
            $loc->name, $loc->address, trim("{$loc->city}, {$loc->state} {$loc->zip}", ', '), $loc->country,
            $loc->phone ? 'Phone: ' . $loc->phone : null,
        ]])
        @if($loc->instructions)
            @include('emails.partials.box', ['title' => 'Pickup instructions', 'lines' => [$loc->instructions]])
        @endif
    @else
        @include('emails.partials.box', ['title' => 'Pickup location', 'lines' => [$order->shipping_address]])
    @endif

    <p style="margin:12px 0 4px;font-weight:bold;">Please bring</p>
    <ul style="margin:0 0 12px;padding-left:20px;">
        <li>A valid photo ID</li>
        <li>Your order number: <strong>{{ $order->order_number }}</strong></li>
    </ul>

    <p style="margin:16px 0 0;font-weight:bold;">Ready for you</p>
    @include('emails.partials.items', ['order' => $order])

    @if($order->user_id)
        @include('emails.partials.button', ['url' => route('account.orders.show', $order), 'label' => 'View your order'])
    @endif
@endsection
