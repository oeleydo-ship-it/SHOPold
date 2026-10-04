@extends('layouts.driver')

@section('title', 'My Deliveries')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800">My Deliveries</h1>
    <div class="flex gap-2">
        <a href="{{ route('driver.orders.index') }}" class="px-3 py-2 rounded-lg {{ !request('status') ? 'bg-driver-600 text-white' : 'bg-white text-gray-600' }}">
            Active
        </a>
        <a href="{{ route('driver.orders.index', ['status' => 'delivered']) }}" class="px-3 py-2 rounded-lg {{ request('status') === 'delivered' ? 'bg-driver-600 text-white' : 'bg-white text-gray-600' }}">
            Delivered
        </a>
    </div>
</div>

@if($orders->isEmpty())
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
        <i class="fas fa-box-open text-4xl mb-3"></i>
        <p>No deliveries found</p>
    </div>
@else
    <div class="space-y-4">
        @foreach($orders as $order)
            <div class="bg-white rounded-xl shadow-sm p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-bold text-lg">{{ $order->order_number }}</span>
                            @if($order->status === 'processing' && !$order->picked_up_at)
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">
                                    <i class="fas fa-hand-holding-box mr-1"></i>Pickup Required
                                </span>
                            @elseif($order->status === 'shipped')
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                                    <i class="fas fa-truck mr-1"></i>In Transit
                                </span>
                            @elseif($order->status === 'delivered')
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                                    <i class="fas fa-check mr-1"></i>Delivered
                                </span>
                            @endif
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-700">
                                    <i class="fas fa-user mr-2 text-gray-400"></i>{{ $order->shipping_name }}
                                </p>
                                <p class="text-sm text-gray-500 ml-6">{{ $order->shipping_phone }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">
                                    <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>Delivery Address
                                </p>
                                <p class="text-sm text-gray-500 ml-6">
                                    {{ $order->shipping_address }}<br>
                                    {{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-4 text-sm text-gray-500">
                            <span><i class="fas fa-box mr-1"></i>{{ $order->items->count() }} items</span>
                            <span><i class="fas fa-money-bill mr-1"></i>{{ format_currency($order->total) }}</span>
                            @if($order->payment_method === 'cod')
                                <span class="text-orange-600 font-medium"><i class="fas fa-hand-holding-usd mr-1"></i>COD</span>
                            @else
                                <span class="text-green-600"><i class="fas fa-credit-card mr-1"></i>Paid</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-2 ml-4">
                        <a href="{{ route('driver.orders.show', $order) }}" 
                           class="px-4 py-2 bg-driver-600 text-white rounded-lg hover:bg-driver-700 text-center">
                            <i class="fas fa-eye mr-1"></i> Details
                        </a>
                        @if($order->shipping_phone)
                            <a href="tel:{{ $order->shipping_phone }}" 
                               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-center">
                                <i class="fas fa-phone mr-1"></i> Call
                            </a>
                        @endif
                        <a href="https://maps.google.com/?q={{ urlencode($order->shipping_address . ', ' . $order->shipping_city) }}" 
                           target="_blank"
                           class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-center">
                            <i class="fas fa-directions mr-1"></i> Navigate
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
@endif
@endsection
