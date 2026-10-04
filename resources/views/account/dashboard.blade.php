@extends('layouts.account')

@section('title', 'My Account')

@section('account_heading', 'My Account')
@section('account_content_class', 'space-y-6')

@section('account_content')
            @if(auth()->user()->is_driver && setting('features.driver_portal', true))
            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl shadow-sm p-4 md:p-6 text-white">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 md:w-14 md:h-14 bg-white/20 rounded-xl flex items-center justify-center mr-3 md:mr-4 flex-shrink-0">
                            <i class="fas fa-truck text-xl md:text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg md:text-xl font-bold">Driver Portal</h3>
                            <p class="text-emerald-100 text-sm">Manage your deliveries</p>
                        </div>
                    </div>
                    <a href="{{ route('driver.dashboard') }}" class="px-4 md:px-6 py-2.5 md:py-3 bg-white text-emerald-600 rounded-lg font-semibold hover:bg-emerald-50 transition text-center text-sm md:text-base">
                        <i class="fas fa-arrow-right mr-2"></i>Go to Portal
                    </a>
                </div>
            </div>
            @endif

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-6">
                <div class="bg-white rounded-xl shadow-sm p-4 md:p-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-shopping-bag text-primary-600 text-lg md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-4 min-w-0">
                            <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $orderCount }}</p>
                            <p class="text-gray-500 text-xs md:text-sm">Orders</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 md:p-6">
                    <div class="flex items-center">
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-heart text-red-600 text-lg md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-4 min-w-0">
                            <p class="text-xl md:text-2xl font-bold text-gray-900">{{ $wishlistCount }}</p>
                            <p class="text-gray-500 text-xs md:text-sm">Wishlist</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 col-span-2 md:col-span-1">
                    <div class="flex items-center">
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user text-green-600 text-lg md:text-xl"></i>
                        </div>
                        <div class="ml-3 md:ml-4 min-w-0 flex-1">
                            <p class="text-sm md:text-base font-medium text-gray-900 truncate">{{ $user->name }}</p>
                            <p class="text-gray-500 text-xs md:text-sm truncate">{{ $user->email }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Orders --}}
            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-4 md:p-6 border-b flex items-center justify-between">
                    <h3 class="text-base md:text-lg font-bold">Recent Orders</h3>
                    <a href="{{ route('account.orders') }}" class="text-primary-600 hover:underline text-sm">View All</a>
                </div>
                
                @if($recentOrders->count() > 0)
                    {{-- Mobile: Card Layout --}}
                    <div class="md:hidden divide-y">
                        @foreach($recentOrders as $order)
                        <a href="{{ route('account.orders.show', $order) }}" class="block p-4 hover:bg-gray-50 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-gray-900 text-sm truncate">{{ $order->order_number }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $order->created_at->format('M d, Y') }}</p>
                                    <div class="mt-2">
                                        {!! $order->status_badge !!}
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="font-bold text-gray-900">{{ format_currency($order->total) }}</p>
                                    <p class="text-xs text-primary-600 mt-1">View <i class="fas fa-chevron-right text-[10px]"></i></p>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    
                    {{-- Desktop: Table Layout --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($recentOrders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium">{{ $order->order_number }}</td>
                                    <td class="px-6 py-4 text-gray-500">{{ $order->created_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-4">{!! $order->status_badge !!}</td>
                                    <td class="px-6 py-4 font-medium">{{ format_currency($order->total) }}</td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('account.orders.show', $order) }}" class="text-primary-600 hover:underline">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                <div class="p-8 md:p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shopping-bag text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 mb-4">No orders yet</p>
                    <a href="{{ route('shop.index') }}" class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition text-sm">
                        <i class="fas fa-shopping-cart mr-2"></i>Start Shopping
                    </a>
                </div>
                @endif
            </div>
@endsection
