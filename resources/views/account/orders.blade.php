@extends('layouts.account')

@section('title', 'My Orders')

@section('account_heading', 'My Orders')

@section('account_content')
            <div class="bg-white rounded-xl shadow-sm">
                @if($orders->count() > 0)
                    {{-- Mobile: Card Layout --}}
                    <div class="md:hidden divide-y">
                        @foreach($orders as $order)
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('account.orders.show', $order) }}" class="font-semibold text-gray-900 text-sm hover:text-primary-600">
                                        {{ $order->order_number }}
                                    </a>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $order->created_at->format('M d, Y') }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="font-bold text-gray-900">{{ format_currency($order->total) }}</p>
                                </div>
                            </div>
                            
                            {{-- Status Badges --}}
                            <div class="flex flex-wrap gap-2 mb-3">
                                {!! $order->status_badge !!}
                                {!! $order->payment_status_badge !!}
                                @php
                                    $hasOnlyDigital = $order->items->every(fn($item) => $item->is_digital || ($item->product && $item->product->product_type === 'digital'));
                                    $physicalItems = $order->items->filter(fn($item) => !$item->is_digital && (!$item->product || $item->product->product_type !== 'digital'));
                                    $deliveredCount = $physicalItems->where('fulfillment_status', 'delivered')->count();
                                    $totalPhysical = $physicalItems->count();
                                    $allDelivered = $deliveredCount === $totalPhysical && $totalPhysical > 0;
                                @endphp
                                @if($hasOnlyDigital)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                        <i class="fas fa-download mr-1"></i>Digital
                                    </span>
                                @elseif($allDelivered)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                        <i class="fas fa-check mr-1"></i>{{ $deliveredCount }}/{{ $totalPhysical }}
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">
                                        <i class="fas fa-clock mr-1"></i>{{ $deliveredCount }}/{{ $totalPhysical }}
                                    </span>
                                @endif
                            </div>
                            
                            {{-- Actions --}}
                            <div class="flex items-center gap-3">
                                <a href="{{ route('account.orders.show', $order) }}" class="flex-1 py-2 text-center text-sm font-medium text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 transition">
                                    <i class="fas fa-eye mr-1"></i>View Details
                                </a>
                                @if($order->payment_status === 'paid')
                                <a href="{{ route('account.orders.invoice', $order) }}" class="py-2 px-3 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    {{-- Desktop: Enhanced Card Layout --}}
                    <div class="hidden md:block">
                        <div class="divide-y divide-gray-100">
                            @foreach($orders as $order)
                            @php
                                $hasOnlyDigital = $order->items->every(fn($item) => $item->is_digital || ($item->product && $item->product->product_type === 'digital'));
                                $physicalItems = $order->items->filter(fn($item) => !$item->is_digital && (!$item->product || $item->product->product_type !== 'digital'));
                                $deliveredCount = $physicalItems->where('fulfillment_status', 'delivered')->count();
                                $totalPhysical = $physicalItems->count();
                                $allDelivered = $deliveredCount === $totalPhysical && $totalPhysical > 0;
                                $firstItems = $order->items->take(3);
                                $remainingCount = $order->items->count() - 3;
                                $totalCount = $order->items->count();
                            @endphp
                            <div class="p-6 hover:bg-gray-50/50 transition-colors">
                                <div class="flex items-start gap-6">
                                    {{-- Product Images Preview --}}
                                    <div class="flex-shrink-0">
                                        <div class="flex -space-x-3">
                                            @foreach($firstItems as $item)
                                            <div class="w-14 h-14 rounded-lg border-2 border-white shadow-sm overflow-hidden bg-gray-100">
                                                @if($item->product && $item->product->image)
                                                    <img src="{{ asset('storage/' . $item->product->image) }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                        <i class="fas fa-box"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            @endforeach
                                            @if($remainingCount > 0)
                                            <div class="w-14 h-14 rounded-lg border-2 border-white shadow-sm bg-gray-100 flex items-center justify-center">
                                                <span class="text-xs font-semibold text-gray-600">+{{ $remainingCount }}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    {{-- Order Details --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-4 mb-3">
                                            <div>
                                                <a href="{{ route('account.orders.show', $order) }}" class="text-base font-semibold text-gray-900 hover:text-primary-600 transition">
                                                    {{ $order->order_number }}
                                                </a>
                                                <p class="text-sm text-gray-500 mt-0.5">
                                                    <i class="far fa-calendar-alt mr-1"></i>{{ $order->created_at->format('F d, Y') }}
                                                    <span class="mx-2 text-gray-300">•</span>
                                                    <i class="far fa-clock mr-1"></i>{{ $order->created_at->format('h:i A') }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-lg font-bold text-gray-900">{{ format_currency($order->total) }}</p>
                                                <p class="text-xs text-gray-500">{{ $totalCount }} {{ Str::plural('item', $totalCount) }}</p>
                                            </div>
                                        </div>
                                        
                                        {{-- Items Summary --}}
                                        <p class="text-sm text-gray-600 mb-3 line-clamp-1">
                                            {{ $order->items->pluck('product.name')->filter()->implode(', ') ?: 'Order items' }}
                                        </p>
                                        
                                        {{-- Status Row --}}
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                {!! $order->status_badge !!}
                                                {!! $order->payment_status_badge !!}
                                                @if($hasOnlyDigital)
                                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                                        <i class="fas fa-download mr-1"></i>Digital Download
                                                    </span>
                                                @elseif($allDelivered)
                                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                                        <i class="fas fa-check-circle mr-1"></i>{{ $deliveredCount }}/{{ $totalPhysical }} Delivered
                                                    </span>
                                                @elseif($deliveredCount > 0)
                                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">
                                                        <i class="fas fa-truck mr-1"></i>{{ $deliveredCount }}/{{ $totalPhysical }} Delivered
                                                    </span>
                                                @elseif($totalPhysical > 0)
                                                    <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">
                                                        <i class="fas fa-hourglass-half mr-1"></i>Processing
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            {{-- Actions --}}
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('account.orders.show', $order) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 transition">
                                                    <i class="fas fa-eye mr-1.5"></i>View Details
                                                </a>
                                                @if($order->payment_status === 'paid')
                                                <a href="{{ route('account.orders.invoice', $order) }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition" title="Download Invoice">
                                                    <i class="fas fa-file-invoice mr-1.5"></i>Invoice
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    
                    {{-- Pagination --}}
                    <div class="p-4 border-t">
                        {{ $orders->links() }}
                    </div>
                @else
                <div class="p-8 md:p-12 text-center">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shopping-bag text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg md:text-xl font-semibold text-gray-900 mb-2">No orders yet</h3>
                    <p class="text-gray-500 mb-6 text-sm md:text-base">Start shopping to see your orders here</p>
                    <a href="{{ route('shop.index') }}" class="inline-flex items-center px-5 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                        <i class="fas fa-shopping-cart mr-2"></i>Start Shopping
                    </a>
                </div>
                @endif
            </div>
@endsection
