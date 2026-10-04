@extends('layouts.vendor')

@section('title', 'Order #' . $order->order_number)
@section('page-title', 'Order Details')

@section('content')
<div class="mb-6">
    <a href="{{ route('vendor.orders.index') }}" class="text-vendor-600 hover:text-vendor-700">
        <i class="fas fa-arrow-left mr-2"></i>Back to Orders
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Order Items -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm">
            <div class="p-6 border-b flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Order #{{ $order->order_number }}</h2>
                    <p class="text-sm text-gray-500">Placed on {{ $order->created_at->format('F d, Y \a\t h:i A') }}</p>
                </div>
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        'processing' => 'bg-blue-100 text-blue-700',
                        'shipped' => 'bg-purple-100 text-purple-700',
                        'delivered' => 'bg-green-100 text-green-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100' }}">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
            
            <!-- Bulk Actions -->
            @php
                $canFulfill = $order->status !== 'cancelled' && 
                              ($order->payment_status === 'paid' || $order->payment_method === 'cod');
            @endphp
            @if($canFulfill && $order->items->where('fulfillment_status', '!=', 'delivered')->count() > 0)
            <div class="p-4 bg-vendor-50 border-b">
                <form action="{{ route('vendor.orders.bulk-status', $order) }}" method="POST" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <span class="text-sm font-medium text-gray-700">Bulk Update All Items:</span>
                    <select name="fulfillment_status" required class="px-3 py-2 border rounded-lg text-sm focus:ring-vendor-500 focus:border-vendor-500">
                        <option value="">Select Status</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                    </select>
                    <input type="text" name="tracking_number" placeholder="Tracking # (optional)" 
                           class="px-3 py-2 border rounded-lg text-sm focus:ring-vendor-500 focus:border-vendor-500">
                    <input type="text" name="shipping_carrier" placeholder="Carrier (optional)" 
                           class="px-3 py-2 border rounded-lg text-sm focus:ring-vendor-500 focus:border-vendor-500">
                    <button type="submit" class="px-4 py-2 bg-vendor-600 text-white rounded-lg text-sm hover:bg-vendor-700 transition">
                        Update All
                    </button>
                </form>
            </div>
            @endif
            
            <div class="p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Your Items in This Order</h3>
                <div class="space-y-4">
                    @foreach($order->items as $item)
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <div class="flex items-start space-x-4">
                            <div class="w-16 h-16 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                                @if($item->product && $item->product->image)
                                <img src="{{ $item->product->image_url }}" 
                                     alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                @elseif($item->product && $item->product->images->first())
                                <img src="{{ asset('storage/' . $item->product->images->first()->image) }}" 
                                     alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-800">{{ $item->product_name }}</h4>
                                        @if($item->variation_options)
                                        <p class="text-sm text-gray-500">
                                            @foreach($item->variation_options as $attr => $val)
                                            <span class="mr-2">{{ $attr }}: {{ $val }}</span>
                                            @endforeach
                                        </p>
                                        @endif
                                        <p class="text-sm text-gray-500">Qty: {{ $item->quantity }} × {{ format_currency($item->price) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold">{{ format_currency($item->total) }}</p>
                                        <span class="inline-block mt-1 px-2 py-1 rounded-full text-xs font-medium {{ $item->fulfillment_status_color }}">
                                            {{ $item->fulfillment_status_label }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Tracking Info -->
                                @if($item->tracking_number || $item->shipped_at)
                                <div class="mt-3 p-2 bg-white rounded border text-sm">
                                    @if($item->shipping_carrier)
                                    <p class="text-gray-600"><span class="font-medium">Carrier:</span> {{ $item->shipping_carrier }}</p>
                                    @endif
                                    @if($item->tracking_number)
                                    <p class="text-gray-600"><span class="font-medium">Tracking:</span> {{ $item->tracking_number }}</p>
                                    @endif
                                    @if($item->shipped_at)
                                    <p class="text-gray-600"><span class="font-medium">Shipped:</span> {{ $item->shipped_at->format('M d, Y h:i A') }}</p>
                                    @endif
                                    @if($item->delivered_at)
                                    <p class="text-gray-600"><span class="font-medium">Delivered:</span> {{ $item->delivered_at->format('M d, Y h:i A') }}</p>
                                    @endif
                                </div>
                                @endif
                                
                                <!-- Fulfillment Actions -->
                                @if($canFulfill && $item->fulfillment_status !== 'delivered' && $item->fulfillment_status !== 'cancelled')
                                <div class="mt-3 pt-3 border-t">
                                    <form action="{{ route('vendor.orders.items.status', $item) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="fulfillment_status" required class="px-3 py-1.5 border rounded-lg text-sm focus:ring-vendor-500 focus:border-vendor-500">
                                            <option value="">Update Status</option>
                                            @if($item->fulfillment_status === 'pending')
                                            <option value="processing">Mark as Processing</option>
                                            @endif
                                            @if(in_array($item->fulfillment_status, ['pending', 'processing']))
                                            <option value="shipped">Mark as Shipped</option>
                                            @endif
                                            @if(in_array($item->fulfillment_status, ['pending', 'processing', 'shipped']))
                                            <option value="delivered">Mark as Delivered</option>
                                            @endif
                                            <option value="cancelled">Cancel Item</option>
                                        </select>
                                        <input type="text" name="tracking_number" placeholder="Tracking #" 
                                               value="{{ $item->tracking_number }}"
                                               class="px-3 py-1.5 border rounded-lg text-sm focus:ring-vendor-500 focus:border-vendor-500 w-32">
                                        <input type="text" name="shipping_carrier" placeholder="Carrier" 
                                               value="{{ $item->shipping_carrier }}"
                                               class="px-3 py-1.5 border rounded-lg text-sm focus:ring-vendor-500 focus:border-vendor-500 w-28">
                                        <button type="submit" class="px-3 py-1.5 bg-vendor-600 text-white rounded-lg text-sm hover:bg-vendor-700 transition">
                                            <i class="fas fa-check mr-1"></i>Update
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Customer Information</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-600 mb-2">Contact</h4>
                    <p class="text-gray-800">{{ $order->user->name ?? $order->shipping_name }}</p>
                    <p class="text-gray-600">{{ $order->user->email ?? $order->email }}</p>
                    <p class="text-gray-600">{{ $order->shipping_phone }}</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-600 mb-2">Shipping Address</h4>
                    <p class="text-gray-800">{{ $order->shipping_name }}</p>
                    <p class="text-gray-600">{{ $order->shipping_address }}</p>
                    <p class="text-gray-600">{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}</p>
                    <p class="text-gray-600">{{ $order->shipping_country }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Fulfillment Progress -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Fulfillment Progress</h3>
            @php
                $totalItems = $order->items->count();
                $pendingItems = $order->items->where('fulfillment_status', 'pending')->count();
                $processingItems = $order->items->where('fulfillment_status', 'processing')->count();
                $shippedItems = $order->items->where('fulfillment_status', 'shipped')->count();
                $deliveredItems = $order->items->where('fulfillment_status', 'delivered')->count();
                $cancelledItems = $order->items->where('fulfillment_status', 'cancelled')->count();
                $progressPercent = $totalItems > 0 ? (($deliveredItems / $totalItems) * 100) : 0;
            @endphp
            
            <div class="mb-4">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">Progress</span>
                    <span class="font-medium">{{ $deliveredItems }}/{{ $totalItems }} Delivered</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-vendor-600 h-2 rounded-full transition-all" style="width: {{ $progressPercent }}%"></div>
                </div>
            </div>
            
            <div class="space-y-2 text-sm">
                @if($pendingItems > 0)
                <div class="flex justify-between">
                    <span class="text-yellow-600"><i class="fas fa-clock mr-1"></i> Pending</span>
                    <span>{{ $pendingItems }}</span>
                </div>
                @endif
                @if($processingItems > 0)
                <div class="flex justify-between">
                    <span class="text-blue-600"><i class="fas fa-cog mr-1"></i> Processing</span>
                    <span>{{ $processingItems }}</span>
                </div>
                @endif
                @if($shippedItems > 0)
                <div class="flex justify-between">
                    <span class="text-purple-600"><i class="fas fa-shipping-fast mr-1"></i> Shipped</span>
                    <span>{{ $shippedItems }}</span>
                </div>
                @endif
                @if($deliveredItems > 0)
                <div class="flex justify-between">
                    <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i> Delivered</span>
                    <span>{{ $deliveredItems }}</span>
                </div>
                @endif
                @if($cancelledItems > 0)
                <div class="flex justify-between">
                    <span class="text-red-600"><i class="fas fa-times-circle mr-1"></i> Cancelled</span>
                    <span>{{ $cancelledItems }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Earnings Summary -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Your Earnings</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Items Total</span>
                    <span class="font-medium">{{ format_currency($vendorTotal) }}</span>
                </div>
                <div class="flex justify-between text-red-600">
                    <span>Commission ({{ Auth::user()->vendor->commission_rate ?? 0 }}%)</span>
                    <span>-{{ format_currency($commission) }}</span>
                </div>
                <div class="border-t pt-3 flex justify-between">
                    <span class="font-semibold text-gray-800">Your Earnings</span>
                    <span class="font-bold text-vendor-600">{{ format_currency($vendorEarnings) }}</span>
                </div>
            </div>
            
            @if($order->payment_status !== 'paid')
            <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                @if($order->payment_method === 'cod')
                <p class="text-sm text-yellow-800"><i class="fas fa-info-circle mr-1"></i> COD order - Payment will be collected on delivery.</p>
                @else
                <p class="text-sm text-yellow-800"><i class="fas fa-exclamation-triangle mr-1"></i> Payment pending. Waiting for payment confirmation.</p>
                @endif
            </div>
            @endif
        </div>

        <!-- Payment Status -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Payment</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Method</span>
                    <span class="font-medium">{{ ucfirst($order->payment_method) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Status</span>
                    @php
                        $paymentColors = [
                            'pending' => 'bg-yellow-100 text-yellow-700',
                            'paid' => 'bg-green-100 text-green-700',
                            'failed' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $paymentColors[$order->payment_status] ?? 'bg-gray-100' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Order Notes -->
        @if($order->notes)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Customer Notes</h3>
            <p class="text-gray-600">{{ $order->notes }}</p>
        </div>
        @endif
    </div>
</div>
@endsection
