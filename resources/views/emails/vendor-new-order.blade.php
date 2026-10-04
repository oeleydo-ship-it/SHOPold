@extends('emails.layouts.branded')

@section('title', 'New order ' . $order->order_number)
@section('preheader', $items->count() . ' item(s) from ' . $vendor->store_name . ' in order ' . $order->order_number)
@section('heading', 'You have a new order')

@section('content')
    <p style="margin:0 0 12px;">Hi {{ $vendor->store_name }},</p>
    <p style="margin:0 0 12px;">Order <strong>#{{ $order->order_number }}</strong> includes items from your store.
        @if($order->payment_status === 'paid' || $order->payment_method === 'cod')
            Please prepare them for shipping.
        @else
            It is waiting for payment; you can fulfil it once it is paid.
        @endif
    </p>

    @include('emails.partials.items', ['order' => $order, 'items' => $items])

    <p style="margin:0 0 4px;text-align:right;font-weight:bold;">Your items: {{ format_currency($itemsTotal) }}</p>

    @include('emails.partials.box', ['title' => 'Ships to', 'lines' => [
        $order->shipping_name,
        collect([$order->shipping_city, $order->shipping_country])->filter()->implode(', '),
        'Full address and contact details are in your vendor portal.',
    ]])

    @include('emails.partials.button', ['url' => route('vendor.orders.show', $order), 'label' => 'Open the order'])
@endsection
