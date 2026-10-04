@extends('layouts.vendor')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<!-- Welcome Banner -->
<div class="bg-gradient-to-r from-vendor-600 to-vendor-700 rounded-2xl p-6 mb-8 text-white">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold mb-2">Welcome back, {{ Auth::user()->name }}!</h2>
            <p class="text-vendor-100">Here's what's happening with your store today.</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <a href="{{ route('vendor.products.create') }}" class="px-4 py-2 bg-white text-vendor-600 rounded-lg font-semibold hover:bg-vendor-50 transition">
                <i class="fas fa-plus mr-2"></i>Add Product
            </a>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Sales -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
            </div>
            @if($salesGrowth != 0)
            <span class="text-sm font-medium {{ $salesGrowth > 0 ? 'text-green-600' : 'text-red-600' }}">
                <i class="fas fa-arrow-{{ $salesGrowth > 0 ? 'up' : 'down' }} mr-1"></i>
                {{ abs(round($salesGrowth, 1)) }}%
            </span>
            @endif
        </div>
        <h3 class="text-2xl font-bold text-gray-800">{{ format_currency($thisMonthSales) }}</h3>
        <p class="text-gray-500 text-sm">This Month Sales</p>
    </div>

    <!-- Available Balance -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-wallet text-blue-600 text-xl"></i>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-800">{{ format_currency($vendor->balance) }}</h3>
        <p class="text-gray-500 text-sm">Available Balance</p>
    </div>

    <!-- Products -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-box text-purple-600 text-xl"></i>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-800">{{ $totalProducts }}</h3>
        <p class="text-gray-500 text-sm">{{ $activeProducts }} Active Products</p>
    </div>

    <!-- Pending Orders -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-clock text-amber-600 text-xl"></i>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-gray-800">{{ $pendingOrders }}</h3>
        <p class="text-gray-500 text-sm">Pending Orders</p>
    </div>
</div>

<!-- Earnings Summary -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-8">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Earnings Summary</h3>
    <div class="grid md:grid-cols-4 gap-6">
        <div class="text-center p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-500 mb-1">Total Sales</p>
            <p class="text-xl font-bold text-gray-800">{{ format_currency($vendor->total_sales) }}</p>
        </div>
        @php
            $adminCommission = $vendor->total_sales * ($vendor->commission_rate / 100);
        @endphp
        <div class="text-center p-4 bg-red-50 rounded-lg">
            <p class="text-sm text-gray-500 mb-1">Platform Fee ({{ $vendor->commission_rate }}%)</p>
            <p class="text-xl font-bold text-red-600">-{{ format_currency($adminCommission) }}</p>
        </div>
        <div class="text-center p-4 bg-green-50 rounded-lg">
            <p class="text-sm text-gray-500 mb-1">Your Earnings</p>
            <p class="text-xl font-bold text-green-600">{{ format_currency($vendor->total_earnings) }}</p>
        </div>
        <div class="text-center p-4 bg-blue-50 rounded-lg">
            <p class="text-sm text-gray-500 mb-1">Available Balance</p>
            <p class="text-xl font-bold text-blue-600">{{ format_currency($vendor->balance) }}</p>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <!-- Sales Chart -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Sales Overview (Last 7 Days)</h3>
        <canvas id="salesChart" height="100"></canvas>
    </div>

    <!-- Top Products -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Top Products</h3>
        <div class="space-y-4">
            @forelse($topProducts as $product)
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                    @if($product->images->first())
                    <img src="{{ asset('storage/' . $product->images->first()->image) }}" 
                         alt="{{ $product->name }}" 
                         class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fas fa-image text-gray-400"></i>
                    </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $product->name }}</p>
                    <p class="text-xs text-gray-500">{{ $product->order_items_count }} orders</p>
                </div>
            </div>
            @empty
            <p class="text-gray-500 text-sm">No products yet</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="mt-8 bg-white rounded-xl shadow-sm">
    <div class="p-6 border-b flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-800">Recent Orders</h3>
        <a href="{{ route('vendor.orders.index') }}" class="text-vendor-600 hover:text-vendor-700 text-sm font-medium">
            View All <i class="fas fa-arrow-right ml-1"></i>
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Products</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($recentOrders as $order)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('vendor.orders.show', $order) }}" class="text-vendor-600 font-medium hover:underline">
                            #{{ $order->order_number }}
                        </a>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $order->user->name ?? 'Guest' }}</td>
                    <td class="px-6 py-4 text-gray-600">{{ $order->items->count() }} item(s)</td>
                    <td class="px-6 py-4 font-medium">{{ format_currency($order->items->sum('total')) }}</td>
                    <td class="px-6 py-4">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'processing' => 'bg-blue-100 text-blue-700',
                                'shipped' => 'bg-purple-100 text-purple-700',
                                'delivered' => 'bg-green-100 text-green-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-500 text-sm">{{ $order->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-shopping-bag text-4xl text-gray-300 mb-3"></i>
                        <p>No orders yet</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode(array_column($chartData, 'date')) !!},
            datasets: [{
                label: 'Sales',
                data: {!! json_encode(array_column($chartData, 'sales')) !!},
                borderColor: '#059669',
                backgroundColor: 'rgba(5, 150, 105, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#059669',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>
@endpush



