@extends('layouts.app')

@section('title', 'Order ' . $order->order_number)

@section('content')
@include('account.partials.skin')
<div class="account-page account-page--detail max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">
    {{-- Mobile Header --}}
    <div class="md:hidden mb-6">
        <a href="{{ route('account.orders') }}" class="inline-flex items-center text-primary-600 text-sm mb-3">
            <i class="fas fa-arrow-left mr-2"></i>Back to Orders
        </a>
        <h1 class="text-xl font-bold">Order Details</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $order->order_number }}</p>
        @if($order->payment_status === 'paid')
        <a href="{{ route('account.orders.invoice', $order) }}" class="inline-flex items-center mt-3 px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-file-invoice mr-2"></i>View Invoice
        </a>
        @if($order->items()->where('is_digital', true)->exists())
        @module('digital_products')
        <a href="{{ route('account.orders.downloads', $order) }}" class="inline-flex items-center mt-3 ml-2 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
            <i class="fas fa-download mr-2"></i>{{ __('messages.downloads') }}
        </a>
        @endmodule
        @endif
        @endif
    </div>
    
    {{-- Desktop Header --}}
    <div class="hidden md:flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold">Order Details</h1>
        <div class="flex items-center gap-4">
            @if($order->payment_status === 'paid')
            <a href="{{ route('account.orders.invoice', $order) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-file-invoice mr-2"></i>View Invoice
            </a>
            @if($order->items()->where('is_digital', true)->exists())
            @module('digital_products')
            <a href="{{ route('account.orders.downloads', $order) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-download mr-2"></i>{{ __('messages.downloads') }}
            </a>
            @endmodule
            @endif
            @endif
            <a href="{{ route('account.orders') }}" class="text-primary-600 hover:underline"><i class="fas fa-arrow-left mr-2"></i>Back to Orders</a>
        </div>
    </div>
    
    {{-- Mobile Order Summary Card (shown at top on mobile) --}}
    <div class="lg:hidden mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm text-gray-500">Total</span>
                <span class="text-xl font-bold text-gray-900">{{ format_currency($order->total) }}</span>
            </div>
            <div class="flex flex-wrap gap-2 mb-3">
                {!! $order->status_badge !!}
                {!! $order->payment_status_badge !!}
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-gray-500">Subtotal</span>
                    <p class="font-medium">{{ format_currency($order->subtotal) }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Shipping</span>
                    <p class="font-medium">{{ format_currency($order->shipping_cost) }}</p>
                </div>
                @if($order->discount > 0)
                <div>
                    <span class="text-gray-500">Discount</span>
                    <p class="font-medium text-green-600">-{{ format_currency($order->discount) }}</p>
                </div>
                @endif
                <div>
                    <span class="text-gray-500">Tax</span>
                    <p class="font-medium">{{ format_currency($order->tax) }}</p>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t">
                <span class="text-xs text-gray-500">Payment Method</span>
                @php
                    $paymentServiceMobile = app(\App\Services\PaymentGatewayService::class);
                    $isCardMobile = $paymentServiceMobile->isCardPayment($order->payment_method ?? 'cod');
                @endphp
                <div class="flex items-center gap-2 mt-1">
                    @if($isCardMobile)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            <i class="fas fa-credit-card mr-1"></i>Card
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-money-bill mr-1"></i>Cash
                        </span>
                    @endif
                    <span class="text-sm font-medium">{{ $paymentServiceMobile->getPaymentMethodName($order->payment_method ?? 'cod') }}</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid lg:grid-cols-3 gap-6 md:gap-8">
        <div class="lg:col-span-2 space-y-4 md:space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-4 md:p-6">
                {{-- Desktop: Order header with number and date --}}
                <div class="hidden md:flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold">Order {{ $order->order_number }}</h3>
                    <span class="text-gray-500">{{ $order->created_at->format('M d, Y h:i A') }}</span>
                </div>
                {{-- Mobile: Compact date display --}}
                <div class="md:hidden mb-4">
                    <p class="text-xs text-gray-500">{{ $order->created_at->format('M d, Y h:i A') }}</p>
                </div>
                <div class="hidden md:flex gap-4 mb-6">
                    {!! $order->status_badge !!}
                    {!! $order->payment_status_badge !!}
                </div>
                
                <!-- Order Status Progress Tracker -->
                @php
                    // Use different statuses for pickup vs delivery orders
                    if ($order->delivery_type === 'pickup') {
                        $statuses = ['pending', 'processing', 'ready_for_pickup', 'delivered'];
                        $statusLabels = [
                            'pending' => 'Placed',
                            'processing' => 'Processing',
                            'ready_for_pickup' => 'Ready for Pickup',
                            'delivered' => 'Picked Up'
                        ];
                        $statusIcons = [
                            'pending' => 'fa-clipboard-check',
                            'processing' => 'fa-cog',
                            'ready_for_pickup' => 'fa-store',
                            'delivered' => 'fa-check-circle'
                        ];
                    } else {
                        $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                        $statusLabels = [
                            'pending' => 'Placed',
                            'processing' => 'Processing',
                            'shipped' => 'Shipped',
                            'delivered' => 'Delivered'
                        ];
                        $statusIcons = [
                            'pending' => 'fa-clipboard-check',
                            'processing' => 'fa-cog',
                            'shipped' => 'fa-truck',
                            'delivered' => 'fa-box-open'
                        ];
                    }
                    
                    $currentStatusIndex = array_search($order->status, $statuses);
                    if ($currentStatusIndex === false) $currentStatusIndex = -1;
                    if ($order->status === 'cancelled') $currentStatusIndex = -2;
                    
                    // Check if order contains only digital items
                    $hasOnlyDigitalItems = $order->items->every(fn($item) => $item->is_digital || ($item->product && $item->product->product_type === 'digital'));
                    $hasAnyDigitalItems = $order->items->contains(fn($item) => $item->is_digital || ($item->product && $item->product->product_type === 'digital'));
                @endphp
                
                @if($order->status !== 'cancelled' && !$hasOnlyDigitalItems)
                <div class="mb-4 md:mb-6 p-3 md:p-4 bg-gradient-to-r from-gray-50 to-white rounded-xl border">
                    <h4 class="text-xs md:text-sm font-semibold text-gray-700 mb-3 md:mb-4">Order Progress</h4>
                    <div class="relative">
                        <!-- Progress Line -->
                        <div class="absolute top-4 md:top-5 left-0 right-0 h-0.5 md:h-1 bg-gray-200 mx-4 md:mx-8"></div>
                        <div class="absolute top-4 md:top-5 left-0 h-0.5 md:h-1 bg-green-500 mx-4 md:mx-8 transition-all duration-500" 
                             style="width: calc({{ $currentStatusIndex >= 0 ? (($currentStatusIndex / 3) * 100) : 0 }}% - 2rem)"></div>
                        
                        <!-- Status Steps -->
                        <div class="relative flex justify-between">
                            @foreach($statuses as $index => $status)
                                @php
                                    $isCompleted = $currentStatusIndex >= $index;
                                    $isCurrent = $currentStatusIndex === $index;
                                @endphp
                                <div class="flex flex-col items-center">
                                    <div class="w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center transition-all duration-300
                                        {{ $isCompleted ? 'bg-green-500 text-white shadow-lg shadow-green-200' : 'bg-gray-200 text-gray-400' }}
                                        {{ $isCurrent ? 'ring-2 md:ring-4 ring-green-200 scale-105 md:scale-110' : '' }}">
                                        @if($isCompleted && !$isCurrent)
                                            <i class="fas fa-check text-xs md:text-sm"></i>
                                        @else
                                            <i class="fas {{ $statusIcons[$status] }} text-xs md:text-sm"></i>
                                        @endif
                                    </div>
                                    <span class="mt-1 md:mt-2 text-[10px] md:text-xs font-medium text-center {{ $isCompleted ? 'text-green-600' : 'text-gray-400' }}">
                                        {{ $statusLabels[$status] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Estimated/Actual Dates -->
                    <div class="mt-3 md:mt-4 pt-3 md:pt-4 border-t border-gray-100 grid grid-cols-4 gap-1 md:gap-2 text-[10px] md:text-xs">
                        <div class="text-center">
                            <span class="text-gray-400 block">Ordered</span>
                            <p class="font-medium text-gray-600">{{ $order->created_at->format('M d') }}</p>
                        </div>
                        <div class="text-center">
                            <span class="text-gray-400 block">Processing</span>
                            <p class="font-medium {{ $currentStatusIndex >= 1 ? 'text-gray-600' : 'text-gray-300' }}">
                                {{ $currentStatusIndex >= 1 ? ($order->updated_at->format('M d')) : '--' }}
                            </p>
                        </div>
                        <div class="text-center">
                            <span class="text-gray-400 block">Shipped</span>
                            <p class="font-medium {{ $order->shipped_at ? 'text-gray-600' : 'text-gray-300' }}">
                                {{ $order->shipped_at ? $order->shipped_at->format('M d') : '--' }}
                            </p>
                        </div>
                        <div class="text-center">
                            <span class="text-gray-400 block">Delivered</span>
                            <p class="font-medium {{ $order->delivered_at ? 'text-green-600' : 'text-gray-300' }}">
                                {{ $order->delivered_at ? $order->delivered_at->format('M d') : '--' }}
                            </p>
                        </div>
                    </div>
                </div>
                @elseif($order->status === 'cancelled')
                <div class="mb-4 md:mb-6 p-3 md:p-4 bg-red-50 rounded-xl border border-red-200">
                    <div class="flex items-center">
                        <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-red-500 text-white flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-times text-sm"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-red-700 text-sm md:text-base">Order Cancelled</p>
                            <p class="text-xs md:text-sm text-red-500">This order has been cancelled</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Digital Order Status --}}
                @if($hasOnlyDigitalItems && $order->status !== 'cancelled')
                <div class="mb-4 md:mb-6 p-3 md:p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200">
                    <div class="flex items-center">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-blue-500 text-white flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-download text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <p class="font-semibold text-blue-800 text-sm md:text-base">Digital Download Order</p>
                            <p class="text-xs md:text-sm text-blue-600">
                                @if($order->payment_status === 'paid')
                                    Your files are ready for download
                                @else
                                    Files will be available after payment is confirmed
                                @endif
                            </p>
                        </div>
                        @if($order->payment_status === 'paid')
                        @module('digital_products')
                        <a href="{{ route('account.orders.downloads', $order) }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-download mr-1"></i>Download
                        </a>
                        @endmodule
                        @endif
                    </div>
                </div>
                @endif

                <!-- Item Fulfillment Progress (only for physical items) -->
                @php
                    $physicalItems = $order->items->filter(fn($item) => !$item->is_digital && (!$item->product || $item->product->product_type !== 'digital'));
                    $totalPhysicalItems = $physicalItems->count();
                    $deliveredItems = $physicalItems->where('fulfillment_status', 'delivered')->count();
                    $shippedItems = $physicalItems->where('fulfillment_status', 'shipped')->count();
                    $progressPercent = $totalPhysicalItems > 0 ? (($deliveredItems / $totalPhysicalItems) * 100) : 0;
                @endphp
                @if($totalPhysicalItems > 0)
                <div class="mb-4 p-3 md:p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs md:text-sm font-medium text-gray-700">Item Fulfillment</span>
                        <span class="text-xs md:text-sm text-gray-500">{{ $deliveredItems }}/{{ $totalPhysicalItems }} Delivered</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 md:h-2">
                        <div class="bg-green-500 h-1.5 md:h-2 rounded-full transition-all" style="width: {{ $progressPercent }}%"></div>
                    </div>
                    @if($shippedItems > 0 && $shippedItems > $deliveredItems)
                        <p class="text-[10px] md:text-xs text-blue-600 mt-2"><i class="fas fa-truck mr-1"></i>{{ $shippedItems - $deliveredItems }} item(s) in transit</p>
                    @endif
                </div>
                @endif

                <div class="divide-y">
                    @foreach($order->items as $item)
                    {{-- Mobile: Stacked layout --}}
                    <div class="py-3 md:py-4 md:hidden">
                        <div class="flex gap-3">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                @if($item->variation && $item->variation->image)
                                    <img src="{{ $item->variation->image_url }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                @elseif($item->product && $item->product->image)
                                    <img src="{{ $item->product->image_url }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center"><i class="fas fa-image text-gray-400 text-sm"></i></div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-sm line-clamp-2">{{ $item->product_name }}</h4>
                                @if($item->variation_options)
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($item->variation_options as $attribute => $value)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800">
                                                {{ $attribute }}: {{ $value }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="flex items-center justify-between mt-2">
                                    <p class="text-xs text-gray-500">Qty: {{ $item->quantity }} × {{ format_currency($item->price) }}</p>
                                    <p class="font-semibold text-sm">{{ format_currency($item->total) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            @if($item->is_digital || ($item->product && $item->product->product_type === 'digital'))
                                <span class="inline-block px-2 py-1 rounded-full text-[10px] font-medium bg-blue-100 text-blue-700">
                                    <i class="fas fa-download mr-1"></i>Digital Download
                                </span>
                            @else
                                <span class="inline-block px-2 py-1 rounded-full text-[10px] font-medium {{ $item->fulfillment_status_color }}">
                                    {{ $item->fulfillment_status_label }}
                                </span>
                                @if($item->tracking_number)
                                    <p class="text-[10px] text-primary-600">
                                        <i class="fas fa-truck mr-1"></i>{{ $item->tracking_number }}
                                    </p>
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    {{-- Desktop: Side by side layout --}}
                    <div class="py-4 hidden md:flex gap-4">
                        <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                            @if($item->variation && $item->variation->image)
                                <img src="{{ $item->variation->image_url }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                            @elseif($item->product && $item->product->image)
                                <img src="{{ $item->product->image_url }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center"><i class="fas fa-image text-gray-400"></i></div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h4 class="font-medium">{{ $item->product_name }}</h4>
                            @if($item->variation_options)
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($item->variation_options as $attribute => $value)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $attribute }}: {{ $value }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            <p class="text-gray-500 mt-1">Qty: {{ $item->quantity }} x {{ format_currency($item->price) }}</p>
                            @if(!$item->is_digital && (!$item->product || $item->product->product_type !== 'digital'))
                                @if($item->tracking_number)
                                    <p class="text-sm text-primary-600 mt-2">
                                        <i class="fas fa-truck mr-1"></i>{{ $item->shipping_carrier ?? 'Tracking' }}: {{ $item->tracking_number }}
                                    </p>
                                @endif
                                @if($item->shipped_at)
                                    <p class="text-xs text-gray-400">Shipped: {{ $item->shipped_at->format('M d, Y h:i A') }}</p>
                                @endif
                                @if($item->delivered_at)
                                    <p class="text-xs text-gray-400">Delivered: {{ $item->delivered_at->format('M d, Y h:i A') }}</p>
                                @endif
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">{{ format_currency($item->total) }}</p>
                            @if($item->is_digital || ($item->product && $item->product->product_type === 'digital'))
                                <span class="inline-block mt-1 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                    <i class="fas fa-download mr-1"></i>Digital
                                </span>
                            @else
                                <span class="inline-block mt-1 px-2 py-1 rounded-full text-xs font-medium {{ $item->fulfillment_status_color }}">
                                    {{ $item->fulfillment_status_label }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @if(!$hasOnlyDigitalItems)
            <div class="bg-white rounded-xl shadow-sm p-4 md:p-6">
                @if($order->delivery_type === 'pickup')
                    <div class="flex items-center justify-between mb-3 md:mb-4">
                        <h3 class="text-base md:text-lg font-bold">Pickup Location</h3>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            <i class="fas fa-store mr-1"></i>Store Pickup
                        </span>
                    </div>
                    @if($order->pickupLocation)
                        <p class="text-gray-700 text-sm md:text-base font-medium">{{ $order->pickupLocation->name }}</p>
                        <p class="text-gray-500 text-sm">{{ $order->pickupLocation->address }}</p>
                        <p class="text-gray-500 text-sm">{{ $order->pickupLocation->city }}, {{ $order->pickupLocation->state }} {{ $order->pickupLocation->zip }}</p>
                        <p class="text-gray-500 text-sm">{{ $order->pickupLocation->country }}</p>
                        @if($order->pickupLocation->phone)
                            <p class="text-gray-500 text-sm mt-2"><i class="fas fa-phone mr-1"></i>{{ $order->pickupLocation->phone }}</p>
                        @endif
                        @if($order->pickupLocation->instructions)
                            <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                                <p class="text-xs text-blue-600"><i class="fas fa-info-circle mr-1"></i>{{ $order->pickupLocation->instructions }}</p>
                            </div>
                        @endif
                    @else
                        <p class="text-gray-500 text-sm">{{ $order->shipping_address }}</p>
                    @endif
                @else
                    <div class="flex items-center justify-between mb-3 md:mb-4">
                        <h3 class="text-base md:text-lg font-bold">Shipping Address</h3>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-truck mr-1"></i>Delivery
                        </span>
                    </div>
                    <p class="text-gray-700 text-sm md:text-base">{{ $order->shipping_name }}</p>
                    <p class="text-gray-500 text-sm">{{ $order->shipping_address }}</p>
                    <p class="text-gray-500 text-sm">{{ $order->shipping_city }}, {{ $order->shipping_state }} {{ $order->shipping_zip }}</p>
                    <p class="text-gray-500 text-sm">{{ $order->shipping_country }}</p>
                    <p class="text-gray-500 text-sm mt-2">{{ $order->shipping_phone }}</p>
                @endif
            </div>
            @endif

            <!-- Driver Rating Section -->
            @if($order->driver && $order->status === 'delivered')
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="text-lg font-bold mb-4">Rate Your Delivery</h3>
                    
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-truck text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium">{{ $order->driver->name }}</p>
                            <p class="text-sm text-gray-500">Your delivery driver</p>
                        </div>
                    </div>

                    @if($order->driver_rating)
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-500 mb-2">Your rating</p>
                            <div class="flex items-center gap-1 text-yellow-400">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $order->driver_rating)
                                        <i class="fas fa-star"></i>
                                    @else
                                        <i class="far fa-star"></i>
                                    @endif
                                @endfor
                                <span class="ml-2 text-gray-700 font-medium">{{ $order->driver_rating }}/5</span>
                            </div>
                            @if($order->driver_rating_comment)
                                <p class="text-sm text-gray-600 mt-2">"{{ $order->driver_rating_comment }}"</p>
                            @endif
                        </div>
                    @else
                        @module('driver_portal')
                        <form action="{{ route('account.orders.rate-driver', $order) }}" method="POST" x-data="{ rating: 5, comment: '' }">
                            @csrf
                            <div class="mb-4">
                                <p class="text-sm text-gray-600 mb-2">How was your delivery experience?</p>
                                <div class="flex gap-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" @click="rating = {{ $i }}" 
                                                class="text-2xl transition-colors"
                                                :class="rating >= {{ $i }} ? 'text-yellow-400' : 'text-gray-300'">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    @endfor
                                </div>
                                <input type="hidden" name="rating" x-model="rating">
                            </div>
                            <div class="mb-4">
                                <textarea name="comment" x-model="comment" rows="2" 
                                          placeholder="Leave a comment (optional)"
                                          class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500"></textarea>
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                                Submit Rating
                            </button>
                        </form>
                        @endmodule
                    @endif
                </div>

                <!-- Tip Driver Section -->
                @if(!$order->driver_tip || $order->driver_tip == 0)
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-bold mb-4">Tip Your Driver</h3>
                        <p class="text-sm text-gray-500 mb-4">Show appreciation for great service</p>
                        @module('driver_portal')
                        <form action="{{ route('account.orders.tip-driver', $order) }}" method="POST" x-data="{ amount: 5 }">
                            @csrf
                            <div class="grid grid-cols-4 gap-2 mb-4">
                                @foreach([2, 5, 10, 20] as $tip)
                                    <button type="button" @click="amount = {{ $tip }}"
                                            class="py-2 rounded-lg border-2 transition-colors"
                                            :class="amount === {{ $tip }} ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 hover:border-gray-300'">
                                        {{ format_currency($tip) }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm text-gray-600 mb-1">Custom amount</label>
                                <input type="number" name="amount" x-model="amount" min="1" step="0.01"
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                <i class="fas fa-heart mr-1"></i> Send Tip
                            </button>
                        </form>
                        @endmodule
                    </div>
                @else
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-heart text-green-600"></i>
                            </div>
                            <p class="font-medium text-gray-700">You tipped {{ format_currency($order->driver_tip) }}</p>
                            <p class="text-sm text-gray-500">Thank you for your generosity!</p>
                        </div>
                    </div>
                @endif
            @endif
        </div>
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-6 sticky top-24">
                <h3 class="text-lg font-bold mb-4">Order Summary</h3>
                <div class="space-y-3 text-gray-600">
                    <div class="flex justify-between"><span>Subtotal</span><span>{{ format_currency($order->subtotal) }}</span></div>
                    @if($order->discount > 0)
                    <div class="flex justify-between text-green-600"><span>Discount</span><span>-{{ format_currency($order->discount) }}</span></div>
                    @endif
                    <div class="flex justify-between"><span>Shipping</span><span>{{ format_currency($order->shipping_cost) }}</span></div>
                    <div class="flex justify-between"><span>Tax</span><span>{{ format_currency($order->tax) }}</span></div>
                    <hr>
                    <div class="flex justify-between text-lg font-bold text-gray-900"><span>Total</span><span>{{ format_currency($order->total) }}</span></div>
                </div>
                <div class="mt-6 pt-6 border-t">
                    <p class="text-sm text-gray-500 mb-2">Payment Method</p>
                    @php
                        $paymentService = app(\App\Services\PaymentGatewayService::class);
                        $isCard = $paymentService->isCardPayment($order->payment_method ?? 'cod');
                    @endphp
                    <div class="flex items-center gap-2 mb-2">
                        @if($isCard)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                <i class="fas fa-credit-card mr-1"></i>Card
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-money-bill mr-1"></i>Cash
                            </span>
                        @endif
                    </div>
                    <p class="font-medium text-sm">{{ $paymentService->getPaymentMethodName($order->payment_method ?? 'cod') }}</p>
                    @if($order->payment_status === 'refunded')
                        <div class="mt-3 p-2 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-xs text-red-600 font-medium"><i class="fas fa-undo mr-1"></i>Refunded</p>
                            @if($order->refunded_at)
                                <p class="text-xs text-red-500">{{ $order->refunded_at->format('M d, Y') }}</p>
                            @endif
                            @if($order->refund_amount)
                                <p class="text-xs text-red-500">{{ format_currency($order->refund_amount) }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
