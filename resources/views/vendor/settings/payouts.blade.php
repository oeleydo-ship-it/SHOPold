@extends('layouts.vendor')

@section('title', 'Payouts')
@section('page-title', 'Payouts')

@section('content')
<!-- Earnings Summary -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-800 mb-4">Earnings Overview</h3>
    <div class="grid md:grid-cols-4 gap-4">
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
            <p class="text-sm text-gray-500 mb-1">Total Earnings</p>
            <p class="text-xl font-bold text-green-600">{{ format_currency($vendor->total_earnings) }}</p>
        </div>
        <div class="text-center p-4 bg-blue-50 rounded-lg">
            <p class="text-sm text-gray-500 mb-1">Paid Out</p>
            <p class="text-xl font-bold text-blue-600">{{ format_currency($vendor->total_paid_out) }}</p>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Payouts List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b">
                <h3 class="text-lg font-bold text-gray-800">Payout History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Method</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($payouts as $payout)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-800">#{{ $payout->id }}</td>
                            <td class="px-6 py-4 font-semibold">{{ format_currency($payout->amount) }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ ucfirst($payout->payment_method) }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'processing' => 'bg-blue-100 text-blue-700',
                                        'completed' => 'bg-green-100 text-green-700',
                                        'failed' => 'bg-red-100 text-red-700',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$payout->status] ?? 'bg-gray-100' }}">
                                    {{ ucfirst($payout->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-sm">{{ $payout->created_at->format('M d, Y') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-wallet text-4xl text-gray-300 mb-3"></i>
                                <p>No payouts yet</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($payouts->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $payouts->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Balance -->
        <div class="bg-gradient-to-br from-vendor-600 to-vendor-700 rounded-xl shadow-sm p-6 text-white">
            <h3 class="text-lg font-bold mb-2">Available Balance</h3>
            <p class="text-4xl font-bold">{{ format_currency($vendor->balance) }}</p>
            <p class="text-vendor-100 text-sm mt-2">Available for withdrawal</p>
        </div>

        <!-- Payment Settings Link -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Payment Method</h3>
            @if(!empty($vendor->payment_info['preferred_method']))
                @php
                    $method = $vendor->payment_info['preferred_method'];
                    $methodLabels = ['bank_transfer' => 'Bank Transfer', 'paypal' => 'PayPal', 'stripe' => 'Stripe'];
                    $methodIcons = ['bank_transfer' => 'fa-university', 'paypal' => 'fa-paypal', 'stripe' => 'fa-stripe'];
                @endphp
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg mb-4">
                    <div class="flex items-center">
                        <i class="fas {{ $methodIcons[$method] ?? 'fa-credit-card' }} text-green-600 mr-3"></i>
                        <span class="font-medium text-green-800">{{ $methodLabels[$method] ?? ucfirst($method) }}</span>
                    </div>
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
            @else
                <div class="p-3 bg-yellow-50 rounded-lg mb-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        No payment method configured
                    </p>
                </div>
            @endif
            <a href="{{ route('vendor.payment-settings') }}" class="block w-full py-2 text-center text-vendor-600 border border-vendor-600 rounded-lg hover:bg-vendor-50 transition">
                <i class="fas fa-cog mr-1"></i>Manage Payment Settings
            </a>
        </div>

        <!-- Request Payout -->
        @if($vendor->balance >= 50)
            @if(!empty($vendor->payment_info['preferred_method']))
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Request Payout</h3>
                <form action="{{ route('vendor.payouts.request') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">{{ current_currency_symbol() }}</span>
                                <input type="number" name="amount" min="50" max="{{ $vendor->balance }}" step="0.01" required
                                       class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                                       placeholder="Minimum {{ format_currency(50) }}">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Max: {{ format_currency($vendor->balance) }}</p>
                        </div>
                        <button type="submit" class="w-full py-3 bg-vendor-600 text-white font-semibold rounded-lg hover:bg-vendor-700 transition">
                            <i class="fas fa-paper-plane mr-1"></i>Request Payout
                        </button>
                    </div>
                </form>
            </div>
            @else
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-center">
                    <i class="fas fa-credit-card text-3xl text-gray-400 mb-3"></i>
                    <p class="text-gray-600 mb-4">Set up your payment method to request payouts.</p>
                    <a href="{{ route('vendor.payment-settings') }}" class="inline-block px-4 py-2 bg-vendor-600 text-white rounded-lg hover:bg-vendor-700 transition">
                        Set Up Payment
                    </a>
                </div>
            </div>
            @endif
        @else
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="text-center">
                <i class="fas fa-info-circle text-3xl text-gray-400 mb-3"></i>
                <p class="text-gray-600">Minimum balance of {{ format_currency(50) }} required to request a payout.</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection



