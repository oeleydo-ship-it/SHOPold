@extends('layouts.driver')

@section('title', 'Profile Settings')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Profile Settings</h1>
    <p class="text-gray-500">Manage your driver profile and preferences</p>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Profile Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Basic Info -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-lg mb-4">Personal Information</h3>
            <form action="{{ route('driver.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="flex items-center mb-6">
                    <div class="relative">
                        <div class="w-20 h-20 rounded-full bg-gray-200 overflow-hidden">
                            @if($driver->avatar)
                                <img src="{{ Storage::url($driver->avatar) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-3xl font-bold text-gray-400">
                                    {{ substr($driver->name, 0, 1) }}
                                </div>
                            @endif
                        </div>
                        <label class="absolute bottom-0 right-0 w-8 h-8 bg-driver-600 text-white rounded-full flex items-center justify-center cursor-pointer hover:bg-driver-700">
                            <i class="fas fa-camera text-sm"></i>
                            <input type="file" name="avatar" class="hidden" accept="image/*">
                        </label>
                    </div>
                    <div class="ml-4">
                        <p class="font-medium">{{ $driver->name }}</p>
                        <p class="text-sm text-gray-500">{{ $driver->email }}</p>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="name" value="{{ old('name', $driver->name) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $driver->email) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
                        @error('email')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="driver_phone" value="{{ old('driver_phone', $driver->driver_phone) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
                        @error('driver_phone')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Info</label>
                        <input type="text" name="driver_vehicle" value="{{ old('driver_vehicle', $driver->driver_vehicle) }}"
                               placeholder="e.g., Honda Civic 2020 - ABC123"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Driver License</label>
                        <input type="text" name="driver_license" value="{{ old('driver_license', $driver->driver_license) }}"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="px-6 py-2 bg-driver-600 text-white rounded-lg hover:bg-driver-700">
                        <i class="fas fa-save mr-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-lg mb-4">Change Password</h3>
            <form action="{{ route('driver.profile.password') }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
                        @error('current_password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
                        @error('password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-driver-500 focus:border-driver-500">
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        <i class="fas fa-lock mr-1"></i> Update Password
                    </button>
                </div>
            </form>
        </div>

        <!-- Availability Schedule -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-lg mb-4">Availability Schedule</h3>
            <form action="{{ route('driver.profile.schedule') }}" method="POST" x-data="scheduleForm()">
                @csrf
                @method('PUT')
                
                <div class="space-y-3">
                    @php
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        $schedule = $driver->driver_availability_schedule ?? [];
                    @endphp
                    
                    @foreach($days as $day)
                        <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                            <label class="flex items-center cursor-pointer w-32">
                                <input type="checkbox" name="schedule[{{ $day }}][enabled]" value="1"
                                       {{ ($schedule[$day]['enabled'] ?? false) ? 'checked' : '' }}
                                       class="w-4 h-4 text-driver-600 rounded focus:ring-driver-500">
                                <span class="ml-2 font-medium capitalize">{{ $day }}</span>
                            </label>
                            <div class="flex items-center gap-2 flex-1">
                                <input type="time" name="schedule[{{ $day }}][start]" 
                                       value="{{ $schedule[$day]['start'] ?? '09:00' }}"
                                       class="px-3 py-1 border rounded-lg text-sm focus:ring-driver-500 focus:border-driver-500">
                                <span class="text-gray-500">to</span>
                                <input type="time" name="schedule[{{ $day }}][end]" 
                                       value="{{ $schedule[$day]['end'] ?? '18:00' }}"
                                       class="px-3 py-1 border rounded-lg text-sm focus:ring-driver-500 focus:border-driver-500">
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    <button type="submit" class="px-6 py-2 bg-driver-600 text-white rounded-lg hover:bg-driver-700">
                        <i class="fas fa-calendar-check mr-1"></i> Save Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Sidebar -->
    <div class="space-y-6">
        <!-- Performance Card -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-lg mb-4">Your Performance</h3>
            <div class="space-y-4">
                <div class="text-center">
                    <div class="text-4xl font-bold text-driver-600">{{ number_format($driver->driver_rating ?? 5, 1) }}</div>
                    <div class="flex justify-center text-yellow-400 my-2">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= ($driver->driver_rating ?? 5))
                                <i class="fas fa-star"></i>
                            @else
                                <i class="far fa-star"></i>
                            @endif
                        @endfor
                    </div>
                    <p class="text-sm text-gray-500">Based on {{ $driver->driver_total_ratings ?? 0 }} ratings</p>
                </div>
                
                <hr>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Deliveries</span>
                    <span class="font-semibold">{{ $driver->driver_total_deliveries ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Earnings</span>
                    <span class="font-semibold text-green-600">{{ format_currency($driver->driver_total_earnings ?? 0) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Commission Rate</span>
                    <span class="font-semibold">{{ $driver->driver_commission_rate ?? 10 }}%</span>
                </div>
            </div>
        </div>

        <!-- Current Status -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-bold text-lg mb-4">Current Status</h3>
            <form action="{{ route('driver.status') }}" method="POST">
                @csrf
                <div class="space-y-2">
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ $driver->driver_status === 'available' ? 'border-green-500 bg-green-50' : '' }}">
                        <input type="radio" name="status" value="available" 
                               {{ $driver->driver_status === 'available' ? 'checked' : '' }}
                               class="w-4 h-4 text-green-600 focus:ring-green-500">
                        <span class="ml-3">
                            <span class="font-medium text-green-700">🟢 Available</span>
                            <p class="text-xs text-gray-500">Ready to accept deliveries</p>
                        </span>
                    </label>
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ $driver->driver_status === 'busy' ? 'border-yellow-500 bg-yellow-50' : '' }}">
                        <input type="radio" name="status" value="busy" 
                               {{ $driver->driver_status === 'busy' ? 'checked' : '' }}
                               class="w-4 h-4 text-yellow-600 focus:ring-yellow-500">
                        <span class="ml-3">
                            <span class="font-medium text-yellow-700">🟡 Busy</span>
                            <p class="text-xs text-gray-500">Currently on a delivery</p>
                        </span>
                    </label>
                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ $driver->driver_status === 'offline' ? 'border-gray-500 bg-gray-50' : '' }}">
                        <input type="radio" name="status" value="offline" 
                               {{ $driver->driver_status === 'offline' ? 'checked' : '' }}
                               class="w-4 h-4 text-gray-600 focus:ring-gray-500">
                        <span class="ml-3">
                            <span class="font-medium text-gray-700">🔴 Offline</span>
                            <p class="text-xs text-gray-500">Not accepting deliveries</p>
                        </span>
                    </label>
                </div>
                <button type="submit" class="w-full mt-4 px-4 py-2 bg-driver-600 text-white rounded-lg hover:bg-driver-700">
                    Update Status
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function scheduleForm() {
    return {}
}
</script>
@endsection
