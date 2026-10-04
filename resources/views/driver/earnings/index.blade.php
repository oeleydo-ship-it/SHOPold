@extends('layouts.driver')

@section('title', 'Earnings')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Earnings</h1>
    <p class="text-gray-500">Track your delivery earnings and performance</p>
</div>

<!-- Period Filter -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('driver.earnings.index', ['period' => 'today']) }}" 
           class="px-4 py-2 rounded-lg {{ $period === 'today' ? 'bg-driver-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            Today
        </a>
        <a href="{{ route('driver.earnings.index', ['period' => 'week']) }}" 
           class="px-4 py-2 rounded-lg {{ $period === 'week' ? 'bg-driver-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            This Week
        </a>
        <a href="{{ route('driver.earnings.index', ['period' => 'month']) }}" 
           class="px-4 py-2 rounded-lg {{ $period === 'month' ? 'bg-driver-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            This Month
        </a>
        <a href="{{ route('driver.earnings.index', ['period' => 'year']) }}" 
           class="px-4 py-2 rounded-lg {{ $period === 'year' ? 'bg-driver-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            This Year
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Earnings</p>
                <p class="text-2xl font-bold text-green-600">{{ format_currency($totalEarnings) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-dollar-sign text-green-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Deliveries</p>
                <p class="text-2xl font-bold text-blue-600">{{ $deliveriesCount }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-box text-blue-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Tips Received</p>
                <p class="text-2xl font-bold text-purple-600">{{ format_currency($tipEarnings) }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-hand-holding-usd text-purple-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Avg per Delivery</p>
                <p class="text-2xl font-bold text-orange-600">{{ format_currency($avgPerDelivery) }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-orange-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Earnings Breakdown -->
<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-lg mb-4">Earnings Breakdown</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                    <span>Delivery Commission</span>
                </div>
                <span class="font-semibold">{{ format_currency($deliveryEarnings) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-purple-500 rounded-full mr-3"></div>
                    <span>Tips</span>
                </div>
                <span class="font-semibold">{{ format_currency($tipEarnings) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                    <span>Bonuses</span>
                </div>
                <span class="font-semibold">{{ format_currency($bonusEarnings) }}</span>
            </div>
            <hr>
            <div class="flex items-center justify-between font-bold">
                <span>Total</span>
                <span class="text-green-600">{{ format_currency($totalEarnings) }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-lg mb-4">Performance Stats</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Your Rating</span>
                <div class="flex items-center">
                    <span class="font-bold text-lg mr-2">{{ number_format($driver->driver_rating ?? 5, 1) }}</span>
                    <div class="flex text-yellow-400">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= ($driver->driver_rating ?? 5))
                                <i class="fas fa-star"></i>
                            @else
                                <i class="far fa-star"></i>
                            @endif
                        @endfor
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Total Deliveries (All Time)</span>
                <span class="font-semibold">{{ $driver->driver_total_deliveries ?? 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Total Earnings (All Time)</span>
                <span class="font-semibold text-green-600">{{ format_currency($driver->driver_total_earnings ?? 0) }}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-600">Commission Rate</span>
                <span class="font-semibold">{{ $driver->driver_commission_rate ?? 10 }}%</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div class="bg-white rounded-xl shadow-sm">
    <div class="p-4 border-b flex items-center justify-between">
        <h3 class="font-bold text-lg">Recent Transactions</h3>
        <a href="{{ route('driver.earnings.history') }}" class="text-driver-600 hover:underline text-sm">View All</a>
    </div>
    
    @if($recentTransactions->isEmpty())
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-receipt text-4xl mb-3"></i>
            <p>No transactions yet</p>
        </div>
    @else
        <div class="divide-y">
            @foreach($recentTransactions as $transaction)
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3
                            {{ $transaction->type === 'payout' ? 'bg-red-100' : 'bg-green-100' }}">
                            @if($transaction->type === 'delivery')
                                <i class="fas fa-box text-green-600"></i>
                            @elseif($transaction->type === 'tip')
                                <i class="fas fa-hand-holding-usd text-purple-600"></i>
                            @elseif($transaction->type === 'bonus')
                                <i class="fas fa-gift text-blue-600"></i>
                            @elseif($transaction->type === 'payout')
                                <i class="fas fa-arrow-down text-red-600"></i>
                            @else
                                <i class="fas fa-exchange-alt text-yellow-600"></i>
                            @endif
                        </div>
                        <div>
                            <p class="font-medium">{{ ucfirst($transaction->type) }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $transaction->description ?? ($transaction->order ? $transaction->order->order_number : 'N/A') }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold {{ $transaction->type === 'payout' ? 'text-red-600' : 'text-green-600' }}">
                            {{ $transaction->type === 'payout' ? '-' : '+' }}{{ format_currency($transaction->amount) }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $transaction->created_at->format('M d, h:i A') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
