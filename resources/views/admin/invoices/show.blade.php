@extends('layouts.admin')

@section('title', 'Invoice - ' . $order->order_number)

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Invoice #{{ $order->order_number }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('admin.invoices.download', $order) }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            <i class="fas fa-file-pdf mr-2"></i>Download PDF
        </a>
        <a href="{{ route('admin.invoices.print', $order) }}" target="_blank" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            <i class="fas fa-print mr-2"></i>Print
        </a>
        <a href="{{ route('admin.orders.show', $order) }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to Order
        </a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-8">
    <!-- Header -->
    <div class="flex justify-between items-start mb-8">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ setting('store.name', 'Shop') }}</h2>
            <p class="text-gray-600">{{ setting('store.address', '') }}</p>
            <p class="text-gray-600">{{ setting('store.email', '') }}</p>
            <p class="text-gray-600">{{ setting('store.phone', '') }}</p>
        </div>
        <div class="text-right">
            <h3 class="text-3xl font-bold text-gray-400">INVOICE</h3>
            <p class="text-lg font-semibold mt-2">#{{ $order->order_number }}</p>
            <p class="text-gray-600">Date: {{ $order->created_at->format('M d, Y') }}</p>
            <p class="text-gray-600">Due: {{ $order->created_at->addDays(30)->format('M d, Y') }}</p>
        </div>
    </div>

    <!-- Bill To / Ship To -->
    <div class="grid md:grid-cols-2 gap-8 mb-8">
        <div>
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-2">Bill To</h4>
            <p class="font-semibold">{{ $order->user->name }}</p>
            <p class="text-gray-600">{{ $order->user->email }}</p>
        </div>
        <div>
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-2">Ship To</h4>
            <p class="font-semibold">{{ $order->shipping_name }}</p>
            <p class="text-gray-600">{{ $order->shipping_address }}</p>
            <p class="text-gray-600">{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}</p>
            <p class="text-gray-600">{{ $order->shipping_country }}</p>
        </div>
    </div>

    <!-- Items Table -->
    <table class="w-full mb-8">
        <thead>
            <tr class="border-b-2 border-gray-200">
                <th class="text-left py-3 text-gray-600">Item</th>
                <th class="text-center py-3 text-gray-600">Qty</th>
                <th class="text-right py-3 text-gray-600">Price</th>
                <th class="text-right py-3 text-gray-600">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr class="border-b border-gray-100">
                <td class="py-4">
                    <p class="font-medium">{{ $item->product_name }}</p>
                    @if($item->variation_options)
                        <p class="text-sm text-blue-600">
                            @foreach($item->variation_options as $attr => $val)
                                <span>{{ $attr }}: {{ $val }}{{ !$loop->last ? ', ' : '' }}</span>
                            @endforeach
                        </p>
                    @endif
                    @if($item->variation && $item->variation->sku)
                        <p class="text-sm text-gray-500">SKU: {{ $item->variation->sku }}</p>
                    @elseif($item->product)
                        <p class="text-sm text-gray-500">SKU: {{ $item->product->sku }}</p>
                    @endif
                </td>
                <td class="py-4 text-center">{{ $item->quantity }}</td>
                <td class="py-4 text-right">${{ number_format($item->price, 2) }}</td>
                <td class="py-4 text-right font-medium">${{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="flex justify-end">
        <div class="w-64">
            <div class="flex justify-between py-2">
                <span class="text-gray-600">Subtotal</span>
                <span>${{ number_format($order->subtotal, 2) }}</span>
            </div>
            @if($order->discount > 0)
            <div class="flex justify-between py-2 text-green-600">
                <span>Discount</span>
                <span>-${{ number_format($order->discount, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between py-2">
                <span class="text-gray-600">Shipping</span>
                <span>${{ number_format($order->shipping_cost, 2) }}</span>
            </div>
            <div class="flex justify-between py-2">
                <span class="text-gray-600">Tax</span>
                <span>${{ number_format($order->tax, 2) }}</span>
            </div>
            <div class="flex justify-between py-3 border-t-2 border-gray-200 font-bold text-lg">
                <span>Total</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Payment Info -->
    <div class="mt-8 pt-8 border-t">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <h4 class="font-semibold mb-2">Payment Information</h4>
                <p class="text-gray-600">Method: {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</p>
                <p class="text-gray-600">Status: 
                    <span class="px-2 py-1 rounded text-xs {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </p>
            </div>
            <div class="text-right">
                <p class="text-gray-500 text-sm">Thank you for your business!</p>
            </div>
        </div>
    </div>
</div>
@endsection
