@extends('layouts.driver')

@section('title', 'Delivery History')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Delivery History</h1>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="{{ route('driver.orders.history') }}" method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm text-gray-600 mb-1">From Date</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" 
                   class="px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
        </div>
        <div>
            <label class="block text-sm text-gray-600 mb-1">To Date</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" 
                   class="px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-driver-600 text-white rounded-lg hover:bg-driver-700">
            <i class="fas fa-filter mr-1"></i> Filter
        </button>
        @if(request()->hasAny(['date_from', 'date_to']))
            <a href="{{ route('driver.orders.history') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Clear
            </a>
        @endif
    </form>
</div>

@if($orders->isEmpty())
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
        <i class="fas fa-history text-4xl mb-3"></i>
        <p>No delivery history found</p>
    </div>
@else
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Order</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Customer</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Address</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Total</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Delivered</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('driver.orders.show', $order) }}" class="font-medium text-driver-600 hover:underline">
                                {{ $order->order_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $order->shipping_name }}</p>
                            <p class="text-sm text-gray-500">{{ $order->shipping_phone }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ Str::limit($order->shipping_address, 30) }}, {{ $order->shipping_city }}
                        </td>
                        <td class="px-4 py-3 font-medium">{{ format_currency($order->total) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $order->delivered_at ? $order->delivered_at->format('M d, Y') : '-' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($order->status === 'delivered')
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs">Delivered</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs">Cancelled</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $orders->withQueryString()->links() }}
    </div>
@endif
@endsection
