@extends('emails.layouts.branded')

@section('title', 'New order ' . $order->order_number)
@section('preheader', 'New order ' . $order->order_number . ' for ' . format_currency($order->total))
@section('heading', 'New order received')

@section('content')
    <p style="margin:0;font-size:18px;font-weight:bold;">Order #{{ $order->order_number }} · {{ format_currency($order->total) }}</p>
    <p style="margin:0 0 4px;font-size:13px;color:#6b7280;">
        {{ $order->created_at->format('F j, Y g:i A') }} · {{ ucwords(str_replace('_', ' ', $order->payment_method)) }} · {{ ucfirst($order->payment_status) }}
    </p>
    <p style="margin:0;font-size:14px;">
        Customer: {{ $order->user ? $order->user->name . ' (' . $order->user->email . ')' : $order->shipping_email . ' (guest)' }}
    </p>

    @include('emails.partials.items', ['order' => $order, 'totals' => true])

    @include('emails.partials.box', ['title' => 'Ship to', 'lines' => [
        $order->shipping_name, $order->shipping_address,
        trim("{$order->shipping_city}, {$order->shipping_state} {$order->shipping_zip}", ', '), $order->shipping_country,
        $order->shipping_phone ? 'Phone: ' . $order->shipping_phone : null,
    ]])

    @include('emails.partials.button', ['url' => route('admin.orders.show', $order->id), 'label' => 'Open in admin'])
@endsection

@section('footer_note')
    <p style="margin:4px 0 0;">Automated notification for store staff.</p>
@endsection
