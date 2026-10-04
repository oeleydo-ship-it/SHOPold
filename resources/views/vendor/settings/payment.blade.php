@extends('layouts.vendor')

@section('title', 'Payment Settings')
@section('page-title', 'Payment Settings')

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form action="{{ route('vendor.payment-settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <!-- Preferred Payment Method -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Preferred Payment Method</h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="preferred_method" value="bank_transfer" 
                               {{ ($vendor->payment_info['preferred_method'] ?? '') == 'bank_transfer' ? 'checked' : '' }}
                               class="peer sr-only">
                        <div class="p-4 border-2 rounded-lg peer-checked:border-vendor-500 peer-checked:bg-vendor-50 transition">
                            <div class="text-center">
                                <i class="fas fa-university text-2xl text-gray-600 peer-checked:text-vendor-600 mb-2"></i>
                                <p class="font-medium">Bank Transfer</p>
                            </div>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="preferred_method" value="paypal"
                               {{ ($vendor->payment_info['preferred_method'] ?? '') == 'paypal' ? 'checked' : '' }}
                               class="peer sr-only">
                        <div class="p-4 border-2 rounded-lg peer-checked:border-vendor-500 peer-checked:bg-vendor-50 transition">
                            <div class="text-center">
                                <i class="fab fa-paypal text-2xl text-blue-600 mb-2"></i>
                                <p class="font-medium">PayPal</p>
                            </div>
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="preferred_method" value="stripe"
                               {{ ($vendor->payment_info['preferred_method'] ?? '') == 'stripe' ? 'checked' : '' }}
                               class="peer sr-only">
                        <div class="p-4 border-2 rounded-lg peer-checked:border-vendor-500 peer-checked:bg-vendor-50 transition">
                            <div class="text-center">
                                <i class="fab fa-stripe text-2xl text-purple-600 mb-2"></i>
                                <p class="font-medium">Stripe</p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Bank Transfer Details -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6" id="bank-section">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-university mr-2 text-gray-600"></i>Bank Account Details
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" 
                               value="{{ $vendor->payment_info['bank']['name'] ?? '' }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                               placeholder="e.g., Chase Bank, Bank of America">
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Holder Name</label>
                            <input type="text" name="bank_account_name"
                                   value="{{ $vendor->payment_info['bank']['account_name'] ?? '' }}"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                                   placeholder="Full name on account">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                            <input type="text" name="bank_account_number"
                                   value="{{ $vendor->payment_info['bank']['account_number'] ?? '' }}"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                                   placeholder="Account number">
                        </div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Routing Number / IBAN</label>
                            <input type="text" name="bank_routing_number"
                                   value="{{ $vendor->payment_info['bank']['routing_number'] ?? '' }}"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                                   placeholder="Routing number or IBAN">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SWIFT/BIC Code (International)</label>
                            <input type="text" name="bank_swift_code"
                                   value="{{ $vendor->payment_info['bank']['swift_code'] ?? '' }}"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                                   placeholder="SWIFT code (optional)">
                        </div>
                    </div>
                </div>
            </div>

            <!-- PayPal Details -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6" id="paypal-section">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fab fa-paypal mr-2 text-blue-600"></i>PayPal Details
                </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PayPal Email Address</label>
                    <input type="email" name="paypal_email"
                           value="{{ $vendor->payment_info['paypal']['email'] ?? '' }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                           placeholder="your-email@example.com">
                    <p class="text-sm text-gray-500 mt-1">We'll send payments to this PayPal account.</p>
                </div>
            </div>

            <!-- Stripe Details -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6" id="stripe-section">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fab fa-stripe mr-2 text-purple-600"></i>Stripe Connect
                </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stripe Account ID</label>
                    <input type="text" name="stripe_account_id"
                           value="{{ $vendor->payment_info['stripe']['account_id'] ?? '' }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                           placeholder="acct_xxxxxxxxxxxxx">
                    <p class="text-sm text-gray-500 mt-1">Your Stripe Connect account ID for direct payouts.</p>
                </div>
            </div>

            <button type="submit" class="w-full py-3 bg-vendor-600 text-white font-semibold rounded-lg hover:bg-vendor-700 transition">
                <i class="fas fa-save mr-2"></i>Save Payment Settings
            </button>
        </form>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Current Balance -->
        <div class="bg-gradient-to-br from-vendor-600 to-vendor-700 rounded-xl shadow-sm p-6 text-white">
            <h3 class="text-lg font-bold mb-2">Available Balance</h3>
            @php
                $balance = $vendor->calculated_balance;
            @endphp
            <p class="text-4xl font-bold">{{ format_currency($balance) }}</p>
            <p class="text-vendor-100 text-sm mt-2">Available for withdrawal</p>
            @if($balance >= 50)
            <a href="{{ route('vendor.payouts') }}" class="mt-4 inline-block px-4 py-2 bg-white text-vendor-600 rounded-lg font-semibold hover:bg-vendor-50 transition">
                Request Payout
            </a>
            @endif
        </div>

        <!-- Info Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Payment Information</h3>
            <div class="space-y-3 text-sm text-gray-600">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2"></i>
                    <p>Payouts are processed within 3-5 business days</p>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2"></i>
                    <p>Minimum payout amount is {{ format_currency(50) }}</p>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-0.5 mr-2"></i>
                    <p>Your payment info is encrypted and secure</p>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                    <p>Commission rate: {{ $vendor->commission_rate }}%</p>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Links</h3>
            <div class="space-y-2">
                <a href="{{ route('vendor.payouts') }}" class="flex items-center text-vendor-600 hover:text-vendor-700">
                    <i class="fas fa-wallet mr-2"></i>View Payout History
                </a>
                <a href="{{ route('vendor.settings') }}" class="flex items-center text-vendor-600 hover:text-vendor-700">
                    <i class="fas fa-cog mr-2"></i>Store Settings
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
