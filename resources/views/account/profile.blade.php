@extends('layouts.account')

@section('title', 'Profile')

@section('account_heading', 'Profile Settings')

@section('account_content')
            <form action="{{ route('account.profile.update') }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Personal Information -->
                <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-4 md:mb-6">
                    <h3 class="font-bold text-base md:text-lg mb-4">Personal Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required 
                                   class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                            @error('name')<p class="text-red-500 text-xs md:text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required 
                                   class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                            @error('email')<p class="text-red-500 text-xs md:text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" 
                                   class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}" 
                                   class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select name="gender" class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                                <option value="">Prefer not to say</option>
                                <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender', $user->gender) == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                            <input type="text" name="company" value="{{ old('company', $user->company) }}" 
                                   class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-4 md:mb-6">
                    <h3 class="font-bold text-base md:text-lg mb-4">Address Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                            <textarea name="address" rows="2" 
                                      class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">{{ old('address', $user->address) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <input type="text" name="city" value="{{ old('city', $user->city) }}" 
                                   class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State / Province</label>
                            <input type="text" name="state" value="{{ old('state', $user->state) }}" 
                                   class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ZIP / Postal Code</label>
                            <input type="text" name="zip_code" value="{{ old('zip_code', $user->zip_code) }}" 
                                   class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                            <input type="text" name="country" value="{{ old('country', $user->country) }}" 
                                   class="w-full px-3 md:px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500 text-sm md:text-base">
                        </div>
                    </div>
                </div>

                {{-- Mobile: Full width button, Desktop: Right aligned --}}
                <div class="flex">
                    <button type="submit" class="w-full md:w-auto md:ml-auto px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition text-sm md:text-base">
                        <i class="fas fa-save mr-2"></i>Update Profile
                    </button>
                </div>
            </form>
@endsection
