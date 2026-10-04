@extends('emails.layouts.branded')

@section('title', 'Order ' . $order->order_number . ' delivered')
@section('preheader', 'Your order ' . $order->order_number . ' has been delivered.')
@section('heading', 'Your order has been delivered')

@section('content')
    <p style="margin:0 0 12px;">Hi {{ $order->shipping_name ?? $order->user?->name ?? 'there' }},</p>
    <p style="margin:0 0 12px;">Great news! Your order <strong>#{{ $order->order_number }}</strong> has been delivered.</p>

    @include('emails.partials.box', ['title' => null, 'lines' => [
        $order->delivered_at ? 'Delivered on ' . \Illuminate\Support\Carbon::parse($order->delivered_at)->format('F j, Y g:i A') : null,
        $order->driver ? 'Delivered by ' . $order->driver->name : null,
        $order->tracking_number ? 'Tracking number: ' . $order->tracking_number : null,
    ]])

    @if($order->user_id)
        @include('emails.partials.button', ['url' => route('account.orders.show', $order), 'label' => 'View your order'])
    @endif

    <p style="margin:0;">We hope you enjoy your purchase! If anything isn't right, just reply to this email.</p>
@endsection
