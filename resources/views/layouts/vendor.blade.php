<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vendor Dashboard') - {{ $appSettings['store_name'] ?? config('app.name') }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        vendor: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link.active { background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; }
        .sidebar-link:hover:not(.active) { background-color: rgba(5, 150, 105, 0.1); }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen" x-data="{ sidebarOpen: true, mobileMenuOpen: false }">
    <!-- Mobile Menu Overlay -->
    <div x-show="mobileMenuOpen" x-cloak @click="mobileMenuOpen = false" 
         class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

    <!-- Sidebar -->
    <aside class="fixed top-0 left-0 z-50 h-screen transition-all duration-300 bg-white shadow-xl border-r"
           :class="sidebarOpen ? 'w-64' : 'w-20'"
           x-show="true"
           :class="{ '-translate-x-full lg:translate-x-0': !mobileMenuOpen, 'translate-x-0': mobileMenuOpen }">
        
        <!-- Logo -->
        <div class="h-16 flex items-center justify-between px-4 border-b bg-gradient-to-r from-vendor-600 to-vendor-700">
            <a href="{{ route('vendor.dashboard') }}" class="flex items-center space-x-3" x-show="sidebarOpen">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                    <i class="fas fa-store text-vendor-600 text-lg"></i>
                </div>
                <span class="text-white font-bold text-lg">Vendor Panel</span>
            </a>
            <a href="{{ route('vendor.dashboard') }}" class="mx-auto" x-show="!sidebarOpen">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                    <i class="fas fa-store text-vendor-600 text-lg"></i>
                </div>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="p-4 space-y-2 overflow-y-auto h-[calc(100vh-4rem)]">
            <!-- Dashboard -->
            <a href="{{ route('vendor.dashboard') }}" 
               class="sidebar-link flex items-center px-4 py-3 rounded-xl transition-all {{ request()->routeIs('vendor.dashboard') ? 'active' : 'text-gray-600' }}">
                <i class="fas fa-tachometer-alt w-6"></i>
                <span class="ml-3" x-show="sidebarOpen">Dashboard</span>
            </a>

            <!-- Products -->
            <a href="{{ route('vendor.products.index') }}" 
               class="sidebar-link flex items-center px-4 py-3 rounded-xl transition-all {{ request()->routeIs('vendor.products.*') ? 'active' : 'text-gray-600' }}">
                <i class="fas fa-box w-6"></i>
                <span class="ml-3" x-show="sidebarOpen">Products</span>
            </a>

            <!-- Stock Management -->
            <a href="{{ route('vendor.stock.index') }}" 
               class="sidebar-link flex items-center px-4 py-3 rounded-xl transition-all {{ request()->routeIs('vendor.stock.*') ? 'active' : 'text-gray-600' }}">
                <i class="fas fa-boxes w-6"></i>
                <span class="ml-3" x-show="sidebarOpen">Stock Management</span>
            </a>

            <!-- Orders -->
            <a href="{{ route('vendor.orders.index') }}" 
               class="sidebar-link flex items-center px-4 py-3 rounded-xl transition-all {{ request()->routeIs('vendor.orders.*') ? 'active' : 'text-gray-600' }}">
                <i class="fas fa-shopping-bag w-6"></i>
                <span class="ml-3" x-show="sidebarOpen">Orders</span>
            </a>

            <div class="border-t my-4"></div>

            <!-- Settings -->
            <a href="{{ route('vendor.settings') }}" 
               class="sidebar-link flex items-center px-4 py-3 rounded-xl transition-all {{ request()->routeIs('vendor.settings*') ? 'active' : 'text-gray-600' }}">
                <i class="fas fa-cog w-6"></i>
                <span class="ml-3" x-show="sidebarOpen">Store Settings</span>
            </a>

            <!-- Payouts -->
            <a href="{{ route('vendor.payouts') }}" 
               class="sidebar-link flex items-center px-4 py-3 rounded-xl transition-all {{ request()->routeIs('vendor.payouts') ? 'active' : 'text-gray-600' }}">
                <i class="fas fa-wallet w-6"></i>
                <span class="ml-3" x-show="sidebarOpen">Payouts</span>
            </a>

            <!-- Payment Settings -->
            <a href="{{ route('vendor.payment-settings') }}" 
               class="sidebar-link flex items-center px-4 py-3 rounded-xl transition-all {{ request()->routeIs('vendor.payment-settings*') ? 'active' : 'text-gray-600' }}">
                <i class="fas fa-credit-card w-6"></i>
                <span class="ml-3" x-show="sidebarOpen">Payment Settings</span>
            </a>

            <div class="border-t my-4"></div>

            <!-- Back to Store -->
            <a href="{{ route('home') }}" 
               class="sidebar-link flex items-center px-4 py-3 rounded-xl transition-all text-gray-600">
                <i class="fas fa-arrow-left w-6"></i>
                <span class="ml-3" x-show="sidebarOpen">Back to Store</span>
            </a>

            <!-- View Store -->
            @if(Auth::user()->vendor)
            <a href="{{ route('shop.vendor', Auth::user()->vendor->slug) }}" target="_blank"
               class="sidebar-link flex items-center px-4 py-3 rounded-xl transition-all text-gray-600">
                <i class="fas fa-external-link-alt w-6"></i>
                <span class="ml-3" x-show="sidebarOpen">View My Store</span>
            </a>
            @endif
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="transition-all duration-300" :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30">
            <div class="flex items-center space-x-4">
                <!-- Toggle Sidebar -->
                <button @click="sidebarOpen = !sidebarOpen" class="hidden lg:block text-gray-500 hover:text-gray-700">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <!-- Mobile Menu Toggle -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden text-gray-500 hover:text-gray-700">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <h1 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
            </div>

            <div class="flex items-center space-x-4">
                <!-- Balance -->
                @if(Auth::user()->vendor)
                <div class="hidden md:flex items-center bg-vendor-50 px-4 py-2 rounded-lg">
                    <i class="fas fa-wallet text-vendor-600 mr-2"></i>
                    <span class="text-vendor-700 font-semibold">
                        {{ $appSettings['currency_symbol'] ?? '$' }}{{ number_format(Auth::user()->vendor->balance, 2) }}
                    </span>
                </div>
                @endif

                <!-- User Menu -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center space-x-3 hover:bg-gray-100 rounded-lg px-3 py-2">
                        <div class="w-8 h-8 bg-vendor-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-vendor-600"></i>
                        </div>
                        <span class="hidden md:block text-gray-700 font-medium">{{ Auth::user()->name }}</span>
                        <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                    </button>

                    <div x-show="open" @click.away="open = false" x-cloak
                         class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border py-2 z-50">
                        <a href="{{ route('account.profile') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-user w-5"></i>
                            <span class="ml-2">My Account</span>
                        </a>
                        <a href="{{ route('vendor.settings') }}" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-store w-5"></i>
                            <span class="ml-2">Store Settings</span>
                        </a>
                        <div class="border-t my-2"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center w-full px-4 py-2 text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt w-5"></i>
                                <span class="ml-2">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-4 lg:p-8">
            <!-- Alerts -->
            @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center">
                <i class="fas fa-check-circle mr-3"></i>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                {{ session('error') }}
            </div>
            @endif

            @if(session('warning'))
            <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-xl flex items-center">
                <i class="fas fa-exclamation-triangle mr-3"></i>
                {{ session('warning') }}
            </div>
            @endif

            <!-- Pending Approval Notice -->
            @if(Auth::user()->vendor && Auth::user()->vendor->status === 'pending')
            <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl flex items-center">
                <i class="fas fa-clock mr-3"></i>
                <div>
                    <strong>Your vendor account is pending approval.</strong>
                    <p class="text-sm mt-1">We're reviewing your application and will get back to you soon.</p>
                </div>
            </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>



