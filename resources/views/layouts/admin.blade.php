<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - LaravelShop Admin</title>
    <script>
        (function () {
            const enableToggle = {{ (setting('theme.enable_dark_mode', false) ? 'true' : 'false') }};
            const defaultDark = {{ (setting('theme.dark_mode', false) ? 'true' : 'false') }};
            const stored = localStorage.getItem('theme');
            const wantsDark = (stored === 'dark') || (!stored && defaultDark);

            if (enableToggle) {
                document.documentElement.classList.toggle('dark', wantsDark);
            } else {
                document.documentElement.classList.toggle('dark', defaultDark);
            }
        })();
    </script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        html, body { height: 100%; overflow-x: hidden; }

        html.dark body { background-color: #0b1220 !important; color: #e5e7eb !important; }
        html.dark .bg-white { background-color: #111827 !important; }
        html.dark .bg-gray-50 { background-color: #0b1220 !important; }
        html.dark .bg-gray-100 { background-color: #111827 !important; }
        html.dark .text-gray-900 { color: #f9fafb !important; }
        html.dark .text-gray-800 { color: #f3f4f6 !important; }
        html.dark .text-gray-700 { color: #e5e7eb !important; }
        html.dark .text-gray-600 { color: #d1d5db !important; }
        html.dark .text-gray-500 { color: #9ca3af !important; }
        html.dark .border-gray-200 { border-color: #374151 !important; }
        html.dark .border-gray-300 { border-color: #4b5563 !important; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('click', function (e) {
                const toggleSwitch = e.target.closest('.toggle-switch');
                if (!toggleSwitch) {
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                const checkbox = toggleSwitch.previousElementSibling;
                if (checkbox && checkbox.tagName === 'INPUT' && checkbox.type === 'checkbox') {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });
    </script>
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: window.innerWidth >= 1024, mobileMenuOpen: false, toast: null }" 
      @resize.window="sidebarOpen = window.innerWidth >= 1024"
      @toast.window="toast = $event.detail; setTimeout(() => toast = null, 4000)">
    
    <!-- Toast Notifications -->
    <div x-show="toast" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         class="fixed bottom-4 right-4 z-[100]">
        <div :class="{
            'bg-green-500': toast?.type === 'success',
            'bg-red-500': toast?.type === 'error',
            'bg-blue-500': toast?.type === 'info',
            'bg-yellow-500': toast?.type === 'warning'
        }" class="text-white px-4 md:px-6 py-3 rounded-lg shadow-lg flex items-center text-sm md:text-base max-w-[90vw]">
            <i :class="{
                'fa-check-circle': toast?.type === 'success',
                'fa-times-circle': toast?.type === 'error',
                'fa-info-circle': toast?.type === 'info',
                'fa-exclamation-triangle': toast?.type === 'warning'
            }" class="fas mr-2"></i>
            <span x-text="toast?.message" class="flex-1"></span>
            <button @click="toast = null" class="ml-4 hover:opacity-75"><i class="fas fa-times"></i></button>
        </div>
    </div>

    @if(session('success'))
    <script>
        document.addEventListener('alpine:init', () => {
            setTimeout(() => window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: '{{ session('success') }}' }})), 100);
        });
    </script>
    @endif
    @if(session('error'))
    <script>
        document.addEventListener('alpine:init', () => {
            setTimeout(() => window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: '{{ session('error') }}' }})), 100);
        });
    </script>
    @endif

    <!-- Mobile Overlay -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileMenuOpen = false"
         class="fixed inset-0 bg-black/50 z-40 lg:hidden"
         x-cloak></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside :class="[
                   mobileMenuOpen ? 'translate-x-0' : '-translate-x-full',
                   'lg:translate-x-0',
                   sidebarOpen ? 'lg:w-64' : 'lg:w-20',
                   'w-72'
               ]" 
               class="bg-gray-900 text-white transition-all duration-300 fixed inset-y-0 left-0 z-50 flex flex-col">
            <div class="p-4 border-b border-gray-800 flex-shrink-0 flex items-center justify-between">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2">
                    @if(setting('store.admin_logo'))
                        <img src="{{ Storage::url(setting('store.admin_logo')) }}" alt="{{ setting('store.name', 'Store') }}" class="h-8 md:h-10 w-auto object-contain">
                    @elseif(setting('store.logo'))
                        <img src="{{ Storage::url(setting('store.logo')) }}" alt="{{ setting('store.name', 'Store') }}" class="h-8 md:h-10 w-auto object-contain">
                    @else
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-white text-sm md:text-base"></i>
                        </div>
                    @endif
                    @if(!setting('store.hide_name_in_header'))
                        <span x-show="sidebarOpen || mobileMenuOpen" class="text-lg md:text-xl font-bold truncate">{{ setting('store.name', 'LaravelShop') }}</span>
                    @endif
                </a>
                <!-- Mobile Close Button -->
                <button @click="mobileMenuOpen = false" class="lg:hidden text-gray-400 hover:text-white p-2">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            @php
                $user = auth()->user();
                $adminRole = $user->admin_role;
                $isSuperAdmin = $adminRole === 'super_admin';
                $isManagerOnly = $adminRole === 'manager';
                $isEditorOnly = $adminRole === 'editor';
                $canAccessOrders = $isSuperAdmin || $isManagerOnly;
                $canAccessUsers = $isSuperAdmin || $isManagerOnly;
                $canAccessSettings = $isSuperAdmin;
                $canAccessReports = $isSuperAdmin || $isManagerOnly;
                $canAccessProducts = true; // All roles can access
                $canAccessContent = $isSuperAdmin || $isEditorOnly; // Pages, Media, Blog - NOT manager
            @endphp
            <nav class="p-3 md:p-4 space-y-1 md:space-y-2 flex-1 overflow-y-auto min-h-0">
                <a href="{{ route('admin.dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.dashboard') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-tachometer-alt w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Dashboard</span>
                </a>
                
                <!-- Products Menu with Submenu -->
                <div x-data="{ open: {{ request()->routeIs('admin.products.*') || request()->routeIs('admin.categories.*') || request()->routeIs('admin.brands.*') || request()->routeIs('admin.attributes.*') || request()->routeIs('admin.tags.*') || request()->routeIs('admin.stock.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="w-full flex items-center justify-between px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.products.*') || request()->routeIs('admin.categories.*') || request()->routeIs('admin.brands.*') || request()->routeIs('admin.attributes.*') || request()->routeIs('admin.tags.*') || request()->routeIs('admin.stock.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                        <div class="flex items-center">
                            <i class="fas fa-box w-5"></i>
                            <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Products</span>
                        </div>
                        <i x-show="sidebarOpen || mobileMenuOpen" class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div x-show="open && (sidebarOpen || mobileMenuOpen)" x-collapse class="mt-1 ml-4 space-y-1">
                        <a href="{{ route('admin.products.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm {{ request()->routeIs('admin.products.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                            <i class="fas fa-list w-4 mr-2"></i>All Products
                        </a>
                        <a href="{{ route('admin.categories.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm {{ request()->routeIs('admin.categories.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                            <i class="fas fa-folder w-4 mr-2"></i>Categories
                        </a>
                        <a href="{{ route('admin.brands.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm {{ request()->routeIs('admin.brands.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                            <i class="fas fa-copyright w-4 mr-2"></i>Brands
                        </a>
                        <a href="{{ route('admin.attributes.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm {{ request()->routeIs('admin.attributes.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                            <i class="fas fa-sliders-h w-4 mr-2"></i>Attributes
                        </a>
                        <a href="{{ route('admin.tags.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm {{ request()->routeIs('admin.tags.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                            <i class="fas fa-tags w-4 mr-2"></i>Tags
                        </a>
                        <a href="{{ route('admin.stock.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm {{ request()->routeIs('admin.stock.*') ? 'bg-gray-800 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                            <i class="fas fa-boxes w-4 mr-2"></i>Stock Management
                        </a>
                    </div>
                </div>
                
                @if($canAccessOrders)
                <a href="{{ route('admin.orders.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.orders.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-shopping-cart w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Orders</span>
                </a>
                <a href="{{ route('admin.transactions.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.transactions.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-exchange-alt w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Transactions</span>
                </a>
                @endif
                @if($canAccessUsers)
                <a href="{{ route('admin.users.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.users.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-users w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Users</span>
                </a>
                @endif
                @if($canAccessOrders)
                <a href="{{ route('admin.coupons.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.coupons.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-tag w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Coupons</span>
                </a>
                @endif
                <a href="{{ route('admin.reviews.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.reviews.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-star w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Reviews</span>
                </a>
                @if($canAccessOrders)
                <a href="{{ route('admin.newsletters.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.newsletters.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-envelope w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Newsletter</span>
                </a>
                <a href="{{ route('admin.settings.shipping') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.settings.shipping') || request()->routeIs('admin.shipping.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-truck w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Shipping</span>
                </a>
                <a href="{{ route('admin.delivery.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.delivery.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-clock w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Delivery</span>
                </a>
                @endif
                @if($canAccessReports)
                <a href="{{ route('admin.reports.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.reports.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Reports</span>
                </a>
                @endif
                @if($canAccessContent)
                <a href="{{ route('admin.pages.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.pages.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-file-alt w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Pages</span>
                </a>
                <a href="{{ route('admin.media.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.media.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-images w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Media</span>
                </a>
                @endif
                @if($canAccessOrders)
                <a href="{{ route('admin.helpdesk.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.helpdesk.*') || request()->routeIs('admin.ai-conversations.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-headset w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Helpdesk</span>
                </a>
                @endif
                
                @if(setting('features.blog_enabled') && $canAccessContent)
                <a href="{{ route('admin.blog.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.blog.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-blog w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Blog</span>
                </a>
                @endif
                
                @if(setting('features.multi_vendor') && $isSuperAdmin)
                <a href="{{ route('admin.vendors.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.vendors.*') && !request()->routeIs('admin.payouts.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-store w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Vendors</span>
                </a>
                <a href="{{ route('admin.payouts.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.payouts.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-money-check-alt w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Payout Requests</span>
                </a>
                @endif
                
                @if(setting('features.loyalty_points') && $isSuperAdmin)
                <a href="{{ route('admin.loyalty.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.loyalty.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-coins w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Loyalty Points</span>
                </a>
                @endif
                
                @if(setting('features.driver_portal') && $canAccessOrders)
                <a href="{{ route('admin.drivers.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.drivers.*') ? 'bg-emerald-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-motorcycle w-5 text-emerald-400"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Drivers</span>
                </a>
                @endif
                
                @if(setting('features.localization_enabled', true) && $isSuperAdmin)
                <hr class="border-gray-800 my-3 md:my-4">
                <a href="{{ route('admin.translations.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.translations.*') ? 'bg-indigo-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-language w-5 text-indigo-400"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Translations</span>
                </a>
                @endif
                @if($canAccessSettings)
                <a href="{{ route('admin.settings.index') }}" @click="mobileMenuOpen = false" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base {{ request()->routeIs('admin.settings.*') ? 'bg-primary-600' : 'hover:bg-gray-800' }}">
                    <i class="fas fa-cog w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">Settings</span>
                </a>
                @endif
                <a href="{{ route('home') }}" target="_blank" class="flex items-center px-3 md:px-4 py-2.5 md:py-3 rounded-lg text-sm md:text-base hover:bg-gray-800">
                    <i class="fas fa-store w-5"></i>
                    <span x-show="sidebarOpen || mobileMenuOpen" class="ml-3">View Store</span>
                    <i x-show="sidebarOpen || mobileMenuOpen" class="fas fa-external-link-alt text-xs ml-auto text-gray-500"></i>
                </a>
            </nav>
            
            <!-- Mobile Logout in Sidebar -->
            <div class="lg:hidden p-4 border-t border-gray-800">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-4 py-3 rounded-lg text-red-400 hover:bg-gray-800">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span class="ml-3">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-20'" class="flex-1 transition-all duration-300 ml-0 min-h-screen flex flex-col">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm sticky top-0 z-30 flex-shrink-0">
                <div class="flex items-center justify-between px-4 md:px-6 py-3 md:py-4">
                    <!-- Left: Menu Button -->
                    <div class="flex items-center gap-3 w-10 lg:w-auto">
                        <!-- Mobile Menu Button -->
                        <button @click="mobileMenuOpen = true" class="lg:hidden text-gray-600 hover:text-gray-900 p-1">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <!-- Desktop Sidebar Toggle -->
                        <button @click="sidebarOpen = !sidebarOpen" class="hidden lg:block text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                    
                    <!-- Center: Logo (Mobile Only) -->
                    <div class="lg:hidden flex-1 flex justify-center">
                        <a href="{{ route('admin.dashboard') }}">
                            @if(setting('store.admin_logo'))
                                <img src="{{ Storage::url(setting('store.admin_logo')) }}" alt="{{ setting('store.name', 'Store') }}" class="h-8 w-auto object-contain">
                            @elseif(setting('store.logo'))
                                <img src="{{ Storage::url(setting('store.logo')) }}" alt="{{ setting('store.name', 'Store') }}" class="h-8 w-auto object-contain">
                            @else
                                <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-shopping-bag text-white text-sm"></i>
                                </div>
                            @endif
                        </a>
                    </div>
                    
                    <!-- Right: User Info -->
                    <div class="flex items-center space-x-3 md:space-x-4 w-10 lg:w-auto justify-end">
                        @if(setting('theme.enable_dark_mode', false))
                        <button type="button" onclick="window.toggleDarkMode && window.toggleDarkMode()" class="text-gray-600 hover:text-gray-900" title="Toggle Dark Mode">
                            <i class="fas fa-moon"></i>
                        </button>
                        @endif
                        <span class="text-gray-700 text-sm md:text-base hidden lg:inline">{{ auth()->user()->name }}</span>
                        <form action="{{ route('logout') }}" method="POST" class="hidden lg:inline">
                            @csrf
                            <button type="submit" class="text-gray-600 hover:text-red-600">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            @if(session('success') || session('error'))
            <div class="px-4 md:px-6 pt-4">
                @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-3 md:px-4 py-2 md:py-3 rounded-lg text-sm md:text-base" x-data="{show:true}" x-show="show">
                    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                    <button @click="show=false" class="float-right">&times;</button>
                </div>
                @endif
                @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-3 md:px-4 py-2 md:py-3 rounded-lg text-sm md:text-base" x-data="{show:true}" x-show="show">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                    <button @click="show=false" class="float-right">&times;</button>
                </div>
                @endif
            </div>
            @endif

            <main class="p-4 md:p-6 flex-1 overflow-y-auto">
                @yield('content')
            </main>
            
            <!-- Admin Footer -->
            <footer class="px-4 md:px-6 py-3 border-t bg-gray-50 text-center text-xs text-gray-500">
                <span>{{ setting('store.name', 'Laravel Shop') }}</span>
                <span class="mx-2">•</span>
                <span>v{{ \App\Services\UpdateService::VERSION }}</span>
                @if(\App\Services\UpdateService::AUTHOR)
                    <span class="mx-2">•</span>
                    <span>by {{ \App\Services\UpdateService::AUTHOR }}</span>
                @endif
            </footer>
        </div>
    </div>
    <script src="{{ asset('js/rich-editor.js') }}"></script>
    @stack('scripts')
    <script>
        // Ensure rich editor is available globally
        if (typeof richEditor === 'undefined') {
            console.error('Rich editor not loaded');
        }
    </script>

    <script>
        window.toggleDarkMode = function () {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            html.classList.toggle('dark', !isDark);
            localStorage.setItem('theme', !isDark ? 'dark' : 'light');
        };
    </script>
</body>
</html>
