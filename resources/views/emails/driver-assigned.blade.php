@extends('emails.layouts.branded')
@php($cash = $order->payment_method === 'cod' && $order->payment_status !== 'paid')

@section('title', 'New delivery ' . $order->order_number)
@section('preheader', 'Deliver order ' . $order->order_number . ' to ' . $order->shipping_city)
@section('heading', 'New delivery assigned')

@section('content')
    <p style="margin:0 0 12px;">Hi {{ $driver->name }},</p>
    <p style="margin:0 0 12px;">Order <strong>#{{ $order->order_number }}</strong> has been assigned to you. Pick it up from the store and deliver it to:</p>

    @include('emails.partials.box', ['title' => $order->shipping_name, 'lines' => [
        $order->shipping_address,
        trim("{$order->shipping_city}, {$order->shipping_state} {$order->shipping_zip}", ', '),
        $order->shipping_country,
        $order->shipping_phone ? 'Phone: ' . $order->shipping_phone : null,
    ]])

    @if($cash)
        <p style="margin:0 0 12px;padding:12px 16px;background:#fef3c7;border-radius:8px;color:#92400e;"><strong>Cash on delivery:</strong> collect {{ format_currency($order->total) }} from the customer.</p>
    @endif

    @include('emails.partials.button', ['url' => route('driver.orders.show', $order), 'label' => 'Open the delivery'])
@endsection
