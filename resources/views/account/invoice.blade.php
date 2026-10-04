@extends('layouts.app')

@section('title', 'Invoice - ' . $order->order_number)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
    {{-- Mobile Header --}}
    <div class="md:hidden mb-6">
        <a href="{{ route('account.orders') }}" class="inline-flex items-center text-primary-600 text-sm mb-3">
            <i class="fas fa-arrow-left mr-2"></i>Back to Orders
        </a>
        <h1 class="text-xl font-bold">Invoice</h1>
        <p class="text-sm text-gray-500 mt-1">#{{ $order->order_number }}</p>
        <a href="{{ route('account.orders.invoice.print', $order) }}" target="_blank" 
           class="inline-flex items-center mt-3 px-4 py-2 bg-primary-600 text-white text-sm rounded-lg hover:bg-primary-700 transition">
            <i class="fas fa-print mr-2"></i>Print Invoice
        </a>
    </div>
    
    {{-- Desktop Header --}}
    <div class="hidden md:flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Invoice #{{ $order->order_number }}</h1>
        <div class="flex gap-3">
            <a href="{{ route('account.orders.invoice.print', $order) }}" target="_blank" 
               class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <i class="fas fa-print mr-2"></i>Print Invoice
            </a>
            <a href="{{ route('account.orders') }}" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to Orders
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-4 md:p-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-6 md:mb-8 pb-6 md:pb-8 border-b">
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-800">{{ setting('store.name', 'Shop') }}</h2>
                @if(setting('store.address'))
                <p class="text-gray-600 mt-1 text-sm md:text-base">{{ setting('store.address') }}</p>
                @endif
                @if(setting('store.email'))
                <p class="text-gray-600 text-sm md:text-base">{{ setting('store.email') }}</p>
                @endif
                @if(setting('store.phone'))
                <p class="text-gray-600 text-sm md:text-base">{{ setting('store.phone') }}</p>
                @endif
            </div>
            <div class="md:text-right">
                <h3 class="text-2xl md:text-3xl font-bold text-gray-400">INVOICE</h3>
                <p class="text-base md:text-lg font-semibold mt-2">#{{ $order->order_number }}</p>
                <p class="text-gray-600 text-sm md:text-base">Date: {{ $order->created_at->format('M d, Y') }}</p>
                <div class="mt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs md:text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i> Paid
                    </span>
                </div>
            </div>
        </div>

        <!-- Bill To / Ship To -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8 mb-6 md:mb-8">
            <div>
                <h4 class="text-xs md:text-sm font-semibold text-gray-500 uppercase mb-2">Bill To</h4>
                <p class="font-semibold text-sm md:text-base">{{ $order->user->name }}</p>
                <p class="text-gray-600 text-sm md:text-base">{{ $order->user->email }}</p>
            </div>
            <div>
                <h4 class="text-xs md:text-sm font-semibold text-gray-500 uppercase mb-2">Ship To</h4>
                <p class="font-semibold text-sm md:text-base">{{ $order->shipping_name }}</p>
                <p class="text-gray-600 text-sm">{{ $order->shipping_address }}</p>
                <p class="text-gray-600 text-sm">{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}</p>
                <p class="text-gray-600 text-sm">{{ $order->shipping_country }}</p>
            </div>
        </div>

        <!-- Items - Mobile Card Layout -->
        <div class="md:hidden mb-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">Items</h4>
            <div class="space-y-3">
                @foreach($order->items as $item)
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="font-medium text-sm">{{ $item->product_name }}</p>
                    @if($item->variation_options)
                        <p class="text-xs text-primary-600 mt-1">
                            @foreach($item->variation_options as $attr => $val)
                                <span>{{ $attr }}: {{ $val }}{{ !$loop->last ? ', ' : '' }}</span>
                            @endforeach
                        </p>
                    @endif
                    @if($item->variation && $item->variation->sku)
                        <p class="text-xs text-gray-500">SKU: {{ $item->variation->sku }}</p>
                    @elseif($item->product && $item->product->sku)
                        <p class="text-xs text-gray-500">SKU: {{ $item->product->sku }}</p>
                    @endif
                    <div class="flex justify-between items-center mt-2 pt-2 border-t border-gray-200">
                        <span class="text-xs text-gray-500">{{ $item->quantity }} × {{ format_currency($item->price) }}</span>
                        <span class="font-semibold text-sm">{{ format_currency($item->total) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Items Table - Desktop -->
        <div class="hidden md:block overflow-x-auto mb-8">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200">
                        <th class="text-left py-3 text-gray-600 font-semibold">Item</th>
                        <th class="text-center py-3 text-gray-600 font-semibold">Qty</th>
                        <th class="text-right py-3 text-gray-600 font-semibold">Price</th>
                        <th class="text-right py-3 text-gray-600 font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr class="border-b border-gray-100">
                        <td class="py-4">
                            <p class="font-medium">{{ $item->product_name }}</p>
                            @if($item->variation_options)
                                <p class="text-sm text-primary-600">
                                    @foreach($item->variation_options as $attr => $val)
                                        <span>{{ $attr }}: {{ $val }}{{ !$loop->last ? ', ' : '' }}</span>
                                    @endforeach
                                </p>
                            @endif
                            @if($item->variation && $item->variation->sku)
                                <p class="text-sm text-gray-500">SKU: {{ $item->variation->sku }}</p>
                            @elseif($item->product && $item->product->sku)
                                <p class="text-sm text-gray-500">SKU: {{ $item->product->sku }}</p>
                            @endif
                        </td>
                        <td class="py-4 text-center">{{ $item->quantity }}</td>
                        <td class="py-4 text-right">{{ format_currency($item->price) }}</td>
                        <td class="py-4 text-right font-medium">{{ format_currency($item->total) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="flex justify-end">
            <div class="w-full md:w-72">
                <div class="flex justify-between py-2 text-sm md:text-base">
                    <span class="text-gray-600">Subtotal</span>
                    <span>{{ format_currency($order->subtotal) }}</span>
                </div>
                @if($order->discount > 0)
                <div class="flex justify-between py-2 text-green-600 text-sm md:text-base">
                    <span>Discount</span>
                    <span>-{{ format_currency($order->discount) }}</span>
                </div>
                @endif
                <div class="flex justify-between py-2 text-sm md:text-base">
                    <span class="text-gray-600">Shipping</span>
                    <span>{{ format_currency($order->shipping_cost) }}</span>
                </div>
                <div class="flex justify-between py-2 text-sm md:text-base">
                    <span class="text-gray-600">Tax</span>
                    <span>{{ format_currency($order->tax) }}</span>
                </div>
                <div class="flex justify-between py-3 border-t-2 border-gray-200 font-bold text-base md:text-lg mt-2">
                    <span>Total</span>
                    <span>{{ format_currency($order->total) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="mt-6 md:mt-8 pt-6 md:pt-8 border-t">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-semibold mb-2 text-sm md:text-base">Payment Information</h4>
                    <p class="text-gray-600 text-sm md:text-base">Method: {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}</p>
                    <p class="text-gray-600 text-sm md:text-base">Status: 
                        <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">Paid</span>
                    </p>
                    @if($order->transaction_id)
                    <p class="text-gray-600 text-sm md:text-base break-all">Transaction ID: {{ $order->transaction_id }}</p>
                    @endif
                </div>
                <div class="md:text-right">
                    <p class="text-gray-500 text-sm">Thank you for your purchase!</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



