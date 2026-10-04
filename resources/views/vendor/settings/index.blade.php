@extends('layouts.vendor')

@section('title', 'Store Settings')
@section('page-title', 'Store Settings')

@section('content')
<form action="{{ route('vendor.settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Store Info -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Store Information</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Store Name *</label>
                        <input type="text" name="store_name" value="{{ old('store_name', $vendor->store_name) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="4"
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">{{ old('description', $vendor->description) }}</textarea>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" value="{{ old('email', $vendor->email) }}" required
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                            <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}" required
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Store Address</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                        <input type="text" name="address" value="{{ old('address', $vendor->address) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                            <input type="text" name="city" value="{{ old('city', $vendor->city) }}" required
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                            <input type="text" name="state" value="{{ old('state', $vendor->state) }}"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                            <input type="text" name="country" value="{{ old('country', $vendor->country) }}" required
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP Code</label>
                            <input type="text" name="zip_code" value="{{ old('zip_code', $vendor->zip_code) }}"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Links -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Social Links</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fab fa-facebook text-blue-600 mr-1"></i> Facebook
                        </label>
                        <input type="url" name="social_links[facebook]" value="{{ old('social_links.facebook', $vendor->social_links['facebook'] ?? '') }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                               placeholder="https://facebook.com/yourstore">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fab fa-twitter text-blue-400 mr-1"></i> Twitter
                        </label>
                        <input type="url" name="social_links[twitter]" value="{{ old('social_links.twitter', $vendor->social_links['twitter'] ?? '') }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                               placeholder="https://twitter.com/yourstore">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fab fa-instagram text-pink-500 mr-1"></i> Instagram
                        </label>
                        <input type="url" name="social_links[instagram]" value="{{ old('social_links.instagram', $vendor->social_links['instagram'] ?? '') }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500"
                               placeholder="https://instagram.com/yourstore">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Save Button -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <button type="submit" class="w-full py-3 bg-vendor-600 text-white font-semibold rounded-lg hover:bg-vendor-700 transition">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>

            <!-- Logo -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Store Logo</h3>
                <div class="text-center">
                    @if($vendor->logo)
                    <img src="{{ asset('storage/' . $vendor->logo) }}" alt="Store Logo" 
                         class="w-32 h-32 object-cover rounded-lg mx-auto mb-4">
                    @else
                    <div class="w-32 h-32 bg-gray-100 rounded-lg mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-store text-4xl text-gray-400"></i>
                    </div>
                    @endif
                    <input type="file" name="logo" accept="image/*" class="hidden" id="logo" 
                           onchange="previewImage(this, 'logoPreview')">
                    <label for="logo" class="cursor-pointer text-vendor-600 hover:text-vendor-700 font-medium">
                        <i class="fas fa-upload mr-1"></i> Upload Logo
                    </label>
                </div>
            </div>

            <!-- Banner -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Store Banner</h3>
                <div class="text-center">
                    @if($vendor->banner)
                    <img src="{{ asset('storage/' . $vendor->banner) }}" alt="Store Banner" 
                         class="w-full h-32 object-cover rounded-lg mb-4">
                    @else
                    <div class="w-full h-32 bg-gray-100 rounded-lg mb-4 flex items-center justify-center">
                        <i class="fas fa-image text-4xl text-gray-400"></i>
                    </div>
                    @endif
                    <input type="file" name="banner" accept="image/*" class="hidden" id="banner">
                    <label for="banner" class="cursor-pointer text-vendor-600 hover:text-vendor-700 font-medium">
                        <i class="fas fa-upload mr-1"></i> Upload Banner
                    </label>
                </div>
            </div>

            <!-- Stats -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Store Stats</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Total Sales</span>
                        <span class="font-semibold">{{ format_currency($vendor->total_sales) }}</span>
                    </div>
                    @php
                        $adminCommission = $vendor->total_sales * ($vendor->commission_rate / 100);
                    @endphp
                    <div class="flex justify-between text-red-600">
                        <span>Platform Fee ({{ $vendor->commission_rate }}%)</span>
                        <span class="font-semibold">-{{ format_currency($adminCommission) }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2">
                        <span class="text-gray-800 font-medium">Your Earnings</span>
                        <span class="font-bold text-vendor-600">{{ format_currency($vendor->total_earnings) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Available Balance</span>
                        <span class="font-semibold text-green-600">{{ format_currency($vendor->balance) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Rating</span>
                        <span class="font-semibold">
                            <i class="fas fa-star text-yellow-400"></i>
                            {{ number_format($vendor->average_rating, 1) }} ({{ $vendor->rating_count }})
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection



