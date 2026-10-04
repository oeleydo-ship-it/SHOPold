@extends('layouts.driver')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Welcome, {{ $driver->name }}!</h1>
    <p class="text-gray-500">Here's your delivery overview</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">Active Orders</p>
                <p class="text-xl font-bold text-driver-600">{{ $assignedOrders->count() }}</p>
            </div>
            <div class="w-10 h-10 bg-driver-100 rounded-full flex items-center justify-center">
                <i class="fas fa-box text-driver-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">Pending Pickup</p>
                <p class="text-xl font-bold text-yellow-600">{{ $pendingPickups }}</p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-hand-holding-box text-yellow-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">Today's Deliveries</p>
                <p class="text-xl font-bold text-green-600">{{ $todayDeliveries }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">This Month</p>
                <p class="text-xl font-bold text-blue-600">{{ $monthDeliveries }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-calendar-check text-blue-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">Today's Earnings</p>
                <p class="text-xl font-bold text-green-600">{{ format_currency($todayEarnings) }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-dollar-sign text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">Week's Earnings</p>
                <p class="text-xl font-bold text-purple-600">{{ format_currency($weekEarnings) }}</p>
            </div>
            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-purple-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions & Rating -->
<div class="grid md:grid-cols-2 gap-4 mb-6">
    <div class="bg-gradient-to-r from-driver-600 to-driver-700 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-driver-100">Your Rating</p>
                <div class="flex items-center mt-1">
                    <span class="text-3xl font-bold mr-2">{{ number_format($driver->driver_rating ?? 5, 1) }}</span>
                    <div class="flex text-yellow-300">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= ($driver->driver_rating ?? 5))
                                <i class="fas fa-star"></i>
                            @else
                                <i class="far fa-star"></i>
                            @endif
                        @endfor
                    </div>
                </div>
                <p class="text-sm text-driver-200 mt-1">{{ $driver->driver_total_ratings ?? 0 }} ratings • {{ $driver->driver_total_deliveries ?? 0 }} total deliveries</p>
            </div>
            <div class="text-right">
                <a href="{{ route('driver.earnings.index') }}" class="inline-block px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 text-sm">
                    View Earnings →
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold mb-3">Quick Actions</h3>
        <div class="grid grid-cols-3 gap-3">
            <a href="{{ route('driver.orders.index') }}" class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                <i class="fas fa-box text-xl text-driver-600 mb-1"></i>
                <span class="text-xs text-gray-600">My Orders</span>
            </a>
            <a href="{{ route('driver.earnings.index') }}" class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                <i class="fas fa-dollar-sign text-xl text-green-600 mb-1"></i>
                <span class="text-xs text-gray-600">Earnings</span>
            </a>
            <a href="{{ route('driver.profile') }}" class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                <i class="fas fa-user-cog text-xl text-blue-600 mb-1"></i>
                <span class="text-xs text-gray-600">Profile</span>
            </a>
        </div>
    </div>
</div>

<!-- Active Deliveries -->
<div class="bg-white rounded-xl shadow-sm">
    <div class="p-4 border-b flex items-center justify-between">
        <h2 class="font-bold text-lg">Active Deliveries</h2>
        <a href="{{ route('driver.orders.index') }}" class="text-driver-600 hover:underline text-sm">View All</a>
    </div>
    
    @if($assignedOrders->isEmpty())
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-inbox text-4xl mb-3"></i>
            <p>No active deliveries at the moment</p>
            <p class="text-sm">New orders will appear here when assigned to you</p>
        </div>
    @else
        <div class="divide-y">
            @foreach($assignedOrders->take(5) as $order)
                <div class="p-4 hover:bg-gray-50">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold">{{ $order->order_number }}</span>
                                @if($order->status === 'processing' && !$order->picked_up_at)
                                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs">Pickup Required</span>
                                @elseif($order->status === 'shipped')
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs">In Transit</span>
                                @else
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full text-xs">{{ ucfirst($order->status) }}</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-user mr-1"></i>{{ $order->shipping_name }}
                            </p>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-map-marker-alt mr-1"></i>{{ $order->shipping_address }}, {{ $order->shipping_city }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $order->items->count() }} item(s) • {{ format_currency($order->total) }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <a href="{{ route('driver.orders.show', $order) }}" 
                               class="px-4 py-2 bg-driver-600 text-white rounded-lg hover:bg-driver-700 text-sm">
                                <i class="fas fa-eye mr-1"></i> View
                            </a>
                            @if($order->shipping_phone)
                                <a href="tel:{{ $order->shipping_phone }}" class="text-driver-600 hover:underline text-sm">
                                    <i class="fas fa-phone mr-1"></i>Call
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
