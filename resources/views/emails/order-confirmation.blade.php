@extends('emails.layouts.branded')

@section('title', 'Order confirmation ' . $order->order_number)
@section('preheader', 'We received your order ' . $order->order_number . ' and will process it shortly.')
@section('heading', 'Thank you for your order!')

@section('content')
    <p style="margin:0 0 12px;">Hi {{ $order->shipping_name ?? $order->user?->name ?? 'there' }},</p>
    <p style="margin:0 0 16px;">We've received your order and will start on it shortly.</p>

    <p style="margin:0;font-size:18px;font-weight:bold;">Order #{{ $order->order_number }}</p>
    <p style="margin:0;font-size:13px;color:#6b7280;">
        {{ $order->created_at->format('F j, Y') }} · {{ ucwords(str_replace('_', ' ', $order->payment_method)) }} · {{ ucfirst($order->payment_status) }}
    </p>

    @include('emails.partials.items', ['order' => $order, 'totals' => true])

    @include('emails.partials.box', ['title' => 'Shipping to', 'lines' => [
        $order->shipping_name, $order->shipping_address,
        trim("{$order->shipping_city}, {$order->shipping_state} {$order->shipping_zip}", ', '), $order->shipping_country,
    ]])

    @if($order->user_id)
        @include('emails.partials.button', ['url' => route('account.orders.show', $order), 'label' => 'View your order'])
    @endif

    <p style="margin:0;">Questions about your order? Just reply to this email.</p>
@endsection
