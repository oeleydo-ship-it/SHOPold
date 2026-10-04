@extends('layouts.app')

@section('title', 'Become a Vendor')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-emerald-500 to-teal-600 py-12">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-8 text-white text-center">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-store text-4xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">Become a Vendor</h1>
                <p class="text-emerald-100">Start selling your products on our marketplace</p>
            </div>

            <!-- Form -->
            <form action="{{ route('vendor.register.store') }}" method="POST" enctype="multipart/form-data" class="p-8">
                @csrf

                @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="space-y-6">
                    <!-- Store Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Store Name *</label>
                        <input type="text" name="store_name" value="{{ old('store_name') }}" required
                               class="w-full px-4 py-3 border rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                               placeholder="Enter your store name">
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Store Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-3 border rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                                  placeholder="Tell customers about your store...">{{ old('description') }}</textarea>
                    </div>

                    <!-- Contact -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required
                               class="w-full px-4 py-3 border rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                               placeholder="Your contact number">
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Address *</label>
                        <input type="text" name="address" value="{{ old('address') }}" required
                               class="w-full px-4 py-3 border rounded-xl focus:ring-emerald-500 focus:border-emerald-500"
                               placeholder="Street address">
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                            <input type="text" name="city" value="{{ old('city') }}" required
                                   class="w-full px-4 py-3 border rounded-xl focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                            <input type="text" name="state" value="{{ old('state') }}"
                                   class="w-full px-4 py-3 border rounded-xl focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                            <input type="text" name="country" value="{{ old('country') }}" required
                                   class="w-full px-4 py-3 border rounded-xl focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>
                            <input type="text" name="zip_code" value="{{ old('zip_code') }}"
                                   class="w-full px-4 py-3 border rounded-xl focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>

                    <!-- Logo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Store Logo</label>
                        <input type="file" name="logo" accept="image/*"
                               class="w-full px-4 py-3 border rounded-xl focus:ring-emerald-500 focus:border-emerald-500">
                        <p class="text-xs text-gray-500 mt-1">Optional. Max 2MB.</p>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-xl hover:from-emerald-700 hover:to-teal-700 transition shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Application
                    </button>

                    <p class="text-center text-sm text-gray-500">
                        By submitting, you agree to our vendor terms and conditions.
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection



