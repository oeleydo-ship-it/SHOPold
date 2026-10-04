@extends('install.layout')

@section('title', 'Admin & Store Setup')

@section('content')
<div class="p-8">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Admin Account & Store Settings</h2>

    @if($errors->has('install'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-red-700"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first('install') }}</p>
        </div>
    @endif

    <form action="{{ route('install.run') }}" method="POST" id="installForm">
        @csrf
        
        <!-- Admin Account -->
        <div class="mb-6">
            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                <i class="fas fa-user-shield text-primary-600 mr-2"></i> Admin Account
            </h3>
            <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Admin Name</label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    @error('admin_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    @error('admin_email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="admin_password" required minlength="8"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        @error('admin_password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="admin_password_confirmation" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Settings -->
        <div class="mb-6">
            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                <i class="fas fa-store text-primary-600 mr-2"></i> Store Settings
            </h3>
            <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Store Name</label>
                    <input type="text" name="store_name" value="{{ old('store_name', 'My Shop') }}" required
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Store Email (optional)</label>
                    <input type="email" name="store_email" value="{{ old('store_email') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                    <select name="store_currency" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="USD" {{ old('store_currency') == 'USD' ? 'selected' : '' }}>USD - US Dollar ($)</option>
                        <option value="EUR" {{ old('store_currency') == 'EUR' ? 'selected' : '' }}>EUR - Euro (€)</option>
                        <option value="GBP" {{ old('store_currency') == 'GBP' ? 'selected' : '' }}>GBP - British Pound (£)</option>
                        <option value="CAD" {{ old('store_currency') == 'CAD' ? 'selected' : '' }}>CAD - Canadian Dollar (C$)</option>
                        <option value="AUD" {{ old('store_currency') == 'AUD' ? 'selected' : '' }}>AUD - Australian Dollar (A$)</option>
                        <option value="INR" {{ old('store_currency') == 'INR' ? 'selected' : '' }}>INR - Indian Rupee (₹)</option>
                        <option value="JPY" {{ old('store_currency') == 'JPY' ? 'selected' : '' }}>JPY - Japanese Yen (¥)</option>
                        <option value="CNY" {{ old('store_currency') == 'CNY' ? 'selected' : '' }}>CNY - Chinese Yuan (¥)</option>
                        <option value="BRL" {{ old('store_currency') == 'BRL' ? 'selected' : '' }}>BRL - Brazilian Real (R$)</option>
                        <option value="MXN" {{ old('store_currency') == 'MXN' ? 'selected' : '' }}>MXN - Mexican Peso ($)</option>
                        <option value="PHP" {{ old('store_currency') == 'PHP' ? 'selected' : '' }}>PHP - Philippine Peso (₱)</option>
                        <option value="THB" {{ old('store_currency') == 'THB' ? 'selected' : '' }}>THB - Thai Baht (฿)</option>
                        <option value="MYR" {{ old('store_currency') == 'MYR' ? 'selected' : '' }}>MYR - Malaysian Ringgit (RM)</option>
                        <option value="IDR" {{ old('store_currency') == 'IDR' ? 'selected' : '' }}>IDR - Indonesian Rupiah (Rp)</option>
                        <option value="SGD" {{ old('store_currency') == 'SGD' ? 'selected' : '' }}>SGD - Singapore Dollar (S$)</option>
                        <option value="AED" {{ old('store_currency') == 'AED' ? 'selected' : '' }}>AED - UAE Dirham (د.إ)</option>
                        <option value="SAR" {{ old('store_currency') == 'SAR' ? 'selected' : '' }}>SAR - Saudi Riyal (﷼)</option>
                        <option value="NGN" {{ old('store_currency') == 'NGN' ? 'selected' : '' }}>NGN - Nigerian Naira (₦)</option>
                        <option value="ZAR" {{ old('store_currency') == 'ZAR' ? 'selected' : '' }}>ZAR - South African Rand (R)</option>
                        <option value="EGP" {{ old('store_currency') == 'EGP' ? 'selected' : '' }}>EGP - Egyptian Pound (E£)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('install.database') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
            <button type="submit" id="installBtn" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-download mr-2"></i> Install Now
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('installForm').addEventListener('submit', function() {
    const btn = document.getElementById('installBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Installing...';
});
</script>
@endsection
