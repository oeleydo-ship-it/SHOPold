@extends('layouts.driver')

@section('title', 'Earnings History')

@section('content')
<div class="mb-6">
    <a href="{{ route('driver.earnings.index') }}" class="text-gray-500 hover:text-gray-700 mb-2 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back to Earnings
    </a>
    <h1 class="text-2xl font-bold text-gray-800">Earnings History</h1>
</div>

<!-- Totals Summary -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <p class="text-sm text-gray-500">Deliveries</p>
        <p class="text-xl font-bold text-green-600">{{ format_currency($totals['delivery'] ?? 0) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <p class="text-sm text-gray-500">Tips</p>
        <p class="text-xl font-bold text-purple-600">{{ format_currency($totals['tip'] ?? 0) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <p class="text-sm text-gray-500">Bonuses</p>
        <p class="text-xl font-bold text-blue-600">{{ format_currency($totals['bonus'] ?? 0) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <p class="text-sm text-gray-500">Adjustments</p>
        <p class="text-xl font-bold text-yellow-600">{{ format_currency($totals['adjustment'] ?? 0) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4 text-center">
        <p class="text-sm text-gray-500">Payouts</p>
        <p class="text-xl font-bold text-red-600">{{ format_currency($totals['payout'] ?? 0) }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="{{ route('driver.earnings.history') }}" method="GET" class="flex flex-wrap gap-4">
        <div>
            <label class="block text-sm text-gray-600 mb-1">Type</label>
            <select name="type" class="px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
                <option value="">All Types</option>
                <option value="delivery" {{ request('type') === 'delivery' ? 'selected' : '' }}>Delivery</option>
                <option value="tip" {{ request('type') === 'tip' ? 'selected' : '' }}>Tip</option>
                <option value="bonus" {{ request('type') === 'bonus' ? 'selected' : '' }}>Bonus</option>
                <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                <option value="payout" {{ request('type') === 'payout' ? 'selected' : '' }}>Payout</option>
            </select>
        </div>
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
        <div class="flex items-end">
            <button type="submit" class="px-4 py-2 bg-driver-600 text-white rounded-lg hover:bg-driver-700">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Transactions List -->
<div class="bg-white rounded-xl shadow-sm">
    @if($transactions->isEmpty())
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-receipt text-4xl mb-3"></i>
            <p>No transactions found</p>
        </div>
    @else
        <div class="divide-y">
            @foreach($transactions as $transaction)
                <div class="p-4 flex items-center justify-between hover:bg-gray-50">
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
                            @if($transaction->order)
                                <a href="{{ route('driver.orders.show', $transaction->order) }}" 
                                   class="text-xs text-driver-600 hover:underline">View Order</a>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold {{ $transaction->type === 'payout' ? 'text-red-600' : 'text-green-600' }}">
                            {{ $transaction->type === 'payout' ? '-' : '+' }}{{ format_currency($transaction->amount) }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $transaction->created_at->format('M d, Y h:i A') }}</p>
                        <span class="text-xs px-2 py-0.5 rounded-full 
                            {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-700' : 
                               ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ ucfirst($transaction->status) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="p-4 border-t">
            {{ $transactions->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
