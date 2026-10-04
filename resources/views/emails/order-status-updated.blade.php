@extends('emails.layouts.branded')
@php($brand = \App\View\EmailBrand::data())
@php($trackingUrl = \App\View\EmailBrand::safeUrl($order->tracking_url))
@php($status = ucwords(str_replace('_', ' ', $order->status)))

@section('title', 'Order ' . $order->order_number . ' updated')
@section('preheader', 'Your order ' . $order->order_number . ' is now ' . strtolower($status) . '.')
@section('heading', 'Order update')

@section('content')
    <p style="margin:0 0 12px;">Hi {{ $order->shipping_name ?? $order->user?->name ?? 'there' }},</p>
    <p style="margin:0 0 12px;">The status of your order <strong>#{{ $order->order_number }}</strong> has changed to:</p>

    <p style="margin:20px 0;text-align:center;">
        <span style="display:inline-block;padding:8px 20px;border-radius:999px;background:{{ $brand['primary'] }};color:#ffffff;font-weight:bold;">{{ $status }}</span>
    </p>

    @if($order->status === 'shipped' && $order->tracking_number)
        @include('emails.partials.box', ['title' => 'Tracking', 'lines' => ['Tracking number: ' . $order->tracking_number]])
        @if($trackingUrl)
            @include('emails.partials.button', ['url' => $trackingUrl, 'label' => 'Track your package'])
        @endif
    @endif

    @include('emails.partials.box', ['title' => 'Order summary', 'lines' => [
        'Order number: ' . $order->order_number,
        'Order date: ' . $order->created_at->format('M d, Y'),
        'Total: ' . format_currency($order->total),
    ]])

    @if($order->user_id)
        @include('emails.partials.button', ['url' => route('account.orders.show', $order), 'label' => 'View your order'])
    @endif
@endsection
