@extends('layouts.vendor')

@section('title', 'Orders')
@section('page-title', 'Orders')

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">My Orders</h1>
        <p class="text-gray-500">Manage and fulfill customer orders</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by order number..." 
                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
        </div>
        <select name="status" class="px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
            <option value="">All Order Status</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
        <select name="fulfillment" class="px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
            <option value="">All Fulfillment</option>
            <option value="pending" {{ request('fulfillment') == 'pending' ? 'selected' : '' }}>Needs Fulfillment</option>
            <option value="processing" {{ request('fulfillment') == 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="shipped" {{ request('fulfillment') == 'shipped' ? 'selected' : '' }}>Shipped</option>
            <option value="delivered" {{ request('fulfillment') == 'delivered' ? 'selected' : '' }}>Delivered</option>
        </select>
        <button type="submit" class="px-6 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
            <i class="fas fa-search mr-2"></i>Filter
        </button>
    </form>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Items</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Your Total</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Fulfillment</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Payment</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($orders as $order)
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        'processing' => 'bg-blue-100 text-blue-700',
                        'shipped' => 'bg-purple-100 text-purple-700',
                        'delivered' => 'bg-green-100 text-green-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                    ];
                    $paymentColors = [
                        'pending' => 'bg-yellow-100 text-yellow-700',
                        'paid' => 'bg-green-100 text-green-700',
                        'failed' => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('vendor.orders.show', $order) }}" class="font-medium text-vendor-600 hover:text-vendor-700">
                            #{{ $order->order_number }}
                        </a>
                        <p class="text-xs text-gray-500">{{ $order->created_at->format('M d, Y') }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <p class="font-medium text-gray-800">{{ $order->user->name ?? $order->shipping_name }}</p>
                        <p class="text-xs text-gray-500">{{ $order->shipping_city }}, {{ $order->shipping_country }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-medium">{{ $order->vendor_items_count ?? $order->items->count() }}</span>
                        <span class="text-gray-500">items</span>
                        @if(isset($order->pending_items) && $order->pending_items > 0)
                        <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded-full">
                            {{ $order->pending_items }} pending
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-semibold">{{ format_currency($order->vendor_total ?? 0) }}</span>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $deliveredCount = $order->items->where('fulfillment_status', 'delivered')->count();
                            $totalCount = $order->items->count();
                            $allDelivered = $deliveredCount === $totalCount;
                            $hasPending = $order->items->where('fulfillment_status', 'pending')->count() > 0;
                        @endphp
                        @if($allDelivered)
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            <i class="fas fa-check-circle mr-1"></i>Fulfilled
                        </span>
                        @elseif($hasPending)
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">
                            <i class="fas fa-clock mr-1"></i>Needs Action
                        </span>
                        @else
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                            <i class="fas fa-shipping-fast mr-1"></i>In Progress
                        </span>
                        @endif
                        <p class="text-xs text-gray-500 mt-1">{{ $deliveredCount }}/{{ $totalCount }} delivered</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $paymentColors[$order->payment_status] ?? 'bg-gray-100' }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('vendor.orders.show', $order) }}" 
                           class="inline-flex items-center px-3 py-1.5 bg-vendor-600 text-white text-sm rounded-lg hover:bg-vendor-700 transition">
                            <i class="fas fa-eye mr-1"></i>View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-shopping-bag text-5xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No orders yet</p>
                            <p class="text-sm text-gray-400 mt-1">Orders containing your products will appear here</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($orders->hasPages())
    <div class="px-6 py-4 border-t">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
