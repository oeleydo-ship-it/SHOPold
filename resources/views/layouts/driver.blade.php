<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Driver Portal') - {{ setting('store.name', 'Shop') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' },
                        driver: { 500: '#10b981', 600: '#059669', 700: '#047857' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Top Navigation -->
    <nav class="bg-driver-600 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('driver.dashboard') }}" class="flex items-center">
                        <i class="fas fa-truck text-2xl mr-2"></i>
                        <span class="font-bold text-lg">Driver Portal</span>
                    </a>
                </div>
                
                <div class="hidden md:flex items-center space-x-4">
                    <a href="{{ route('driver.dashboard') }}" class="px-3 py-2 rounded-lg hover:bg-driver-700 {{ request()->routeIs('driver.dashboard') ? 'bg-driver-700' : '' }}">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                    <a href="{{ route('driver.orders.index') }}" class="px-3 py-2 rounded-lg hover:bg-driver-700 {{ request()->routeIs('driver.orders.index') || request()->routeIs('driver.orders.show') ? 'bg-driver-700' : '' }}">
                        <i class="fas fa-box mr-1"></i> Deliveries
                    </a>
                    <a href="{{ route('driver.earnings.index') }}" class="px-3 py-2 rounded-lg hover:bg-driver-700 {{ request()->routeIs('driver.earnings.*') ? 'bg-driver-700' : '' }}">
                        <i class="fas fa-dollar-sign mr-1"></i> Earnings
                    </a>
                    <a href="{{ route('driver.orders.history') }}" class="px-3 py-2 rounded-lg hover:bg-driver-700 {{ request()->routeIs('driver.orders.history') ? 'bg-driver-700' : '' }}">
                        <i class="fas fa-history mr-1"></i> History
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Driver Status -->
                    <form action="{{ route('driver.status') }}" method="POST" class="flex items-center" x-data>
                        @csrf
                        <select name="status" @change="$el.form.submit()" 
                                class="bg-driver-700 border-0 rounded-lg text-sm px-3 py-1.5 focus:ring-2 focus:ring-white">
                            <option value="available" {{ auth()->user()->driver_status === 'available' ? 'selected' : '' }}>🟢 Available</option>
                            <option value="busy" {{ auth()->user()->driver_status === 'busy' ? 'selected' : '' }}>🟡 Busy</option>
                            <option value="offline" {{ auth()->user()->driver_status === 'offline' ? 'selected' : '' }}>🔴 Offline</option>
                        </select>
                    </form>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 hover:bg-driver-700 px-3 py-2 rounded-lg">
                            <div class="w-8 h-8 bg-white text-driver-600 rounded-full flex items-center justify-center font-bold">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <span class="hidden md:block">{{ auth()->user()->name }}</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 text-gray-700">
                            <a href="{{ route('driver.profile') }}" class="block px-4 py-2 hover:bg-gray-100">
                                <i class="fas fa-user-cog mr-2"></i> Profile Settings
                            </a>
                            <a href="{{ route('driver.notifications.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                <i class="fas fa-bell mr-2"></i> Notifications
                            </a>
                            <a href="{{ route('home') }}" class="block px-4 py-2 hover:bg-gray-100">
                                <i class="fas fa-store mr-2"></i> Visit Store
                            </a>
                            <hr class="my-2">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-red-600">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Notifications Bell -->
                    <div class="relative" x-data="{ open: false, count: 0 }" x-init="
                        fetch('{{ route('driver.notifications.unread-count') }}')
                            .then(r => r.json())
                            .then(d => count = d.count)
                    ">
                        <button @click="open = !open" class="relative p-2 hover:bg-driver-700 rounded-lg">
                            <i class="fas fa-bell text-lg"></i>
                            <span x-show="count > 0" x-text="count" 
                                  class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"></span>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak
                             class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg text-gray-700 max-h-96 overflow-y-auto">
                            <div class="p-3 border-b flex justify-between items-center">
                                <span class="font-semibold">Notifications</span>
                                <a href="{{ route('driver.notifications.index') }}" class="text-sm text-driver-600 hover:underline">View All</a>
                            </div>
                            <div class="p-2 text-center text-gray-500 text-sm">
                                <a href="{{ route('driver.notifications.index') }}">See all notifications</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t shadow-lg z-50">
        <div class="flex justify-around py-2">
            <a href="{{ route('driver.dashboard') }}" class="flex flex-col items-center px-3 py-2 {{ request()->routeIs('driver.dashboard') ? 'text-driver-600' : 'text-gray-500' }}">
                <i class="fas fa-home text-xl"></i>
                <span class="text-xs mt-1">Home</span>
            </a>
            <a href="{{ route('driver.orders.index') }}" class="flex flex-col items-center px-3 py-2 {{ request()->routeIs('driver.orders.index') || request()->routeIs('driver.orders.show') ? 'text-driver-600' : 'text-gray-500' }}">
                <i class="fas fa-box text-xl"></i>
                <span class="text-xs mt-1">Deliveries</span>
            </a>
            <a href="{{ route('driver.earnings.index') }}" class="flex flex-col items-center px-3 py-2 {{ request()->routeIs('driver.earnings.*') ? 'text-driver-600' : 'text-gray-500' }}">
                <i class="fas fa-dollar-sign text-xl"></i>
                <span class="text-xs mt-1">Earnings</span>
            </a>
            <a href="{{ route('driver.profile') }}" class="flex flex-col items-center px-3 py-2 {{ request()->routeIs('driver.profile') ? 'text-driver-600' : 'text-gray-500' }}">
                <i class="fas fa-user text-xl"></i>
                <span class="text-xs mt-1">Profile</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6 pb-24 md:pb-6">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-700 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
