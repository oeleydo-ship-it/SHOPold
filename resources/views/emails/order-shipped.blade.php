@extends('emails.layouts.branded')
@php($brand = \App\View\EmailBrand::data())
@php($trackingUrl = \App\View\EmailBrand::safeUrl($order->tracking_url))

@section('title', 'Order ' . $order->order_number . ' shipped')
@section('preheader', 'Your order ' . $order->order_number . ' is on its way.')
@section('heading', 'Your order is on its way!')

@section('content')
    <p style="margin:0 0 12px;">Hi {{ $order->shipping_name ?? $order->user?->name ?? 'there' }},</p>
    <p style="margin:0 0 12px;">Your order <strong>#{{ $order->order_number }}</strong> has been shipped.</p>

    @if($order->tracking_number)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:16px 0;border:2px solid {{ $brand['primary'] }};border-radius:8px;">
            <tr>
                <td align="center" style="padding:16px;">
                    <p style="margin:0 0 4px;font-size:13px;color:#6b7280;">Tracking number</p>
                    <p style="margin:0;font-size:22px;font-weight:bold;letter-spacing:2px;color:{{ $brand['primary'] }};">{{ $order->tracking_number }}</p>
                </td>
            </tr>
        </table>
        @if($trackingUrl)
            @include('emails.partials.button', ['url' => $trackingUrl, 'label' => 'Track your package'])
        @endif
    @endif

    <p style="margin:16px 0 0;font-weight:bold;">In this shipment</p>
    @include('emails.partials.items', ['order' => $order])

    @include('emails.partials.box', ['title' => 'Shipping to', 'lines' => [
        $order->shipping_name, $order->shipping_address,
        trim("{$order->shipping_city}, {$order->shipping_state} {$order->shipping_zip}", ', '), $order->shipping_country,
    ]])

    @if($order->user_id)
        @include('emails.partials.button', ['url' => route('account.orders.show', $order), 'label' => 'View your order', 'outline' => true])
    @endif
@endsection
