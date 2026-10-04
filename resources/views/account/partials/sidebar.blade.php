{{-- Mobile Navigation (Horizontal Scroll) --}}
<div class="lg:hidden mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        {{-- User Info - Compact --}}
        <div class="flex items-center gap-3 mb-4 pb-4 border-b">
            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user text-xl text-primary-600"></i>
            </div>
            <div class="min-w-0">
                <h3 class="font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</h3>
                <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>
        
        {{-- Horizontal Scrollable Navigation --}}
        <div class="overflow-x-auto -mx-4 px-4 scrollbar-hide">
            <div class="flex gap-2 min-w-max">
                <a href="{{ route('account.dashboard') }}" 
                   class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('account.dashboard') ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('account.orders') }}" 
                   class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('account.orders*') ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                </a>
                @module('wishlist')
                <a href="{{ route('account.wishlist') }}" 
                   class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('account.wishlist') ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-heart"></i>
                    <span>Wishlist</span>
                </a>
                @endmodule
                <a href="{{ route('account.profile') }}" 
                   class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('account.profile') ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile</span>
                </a>
                <a href="{{ route('account.password') }}" 
                   class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('account.password') ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-lock"></i>
                    <span>Password</span>
                </a>
                <a href="{{ route('helpdesk.index') }}" 
                   class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium whitespace-nowrap transition {{ request()->routeIs('helpdesk.*') ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-headset"></i>
                    <span>Support</span>
                </a>
                @if(auth()->user()->is_driver && setting('features.driver_portal', true))
                <a href="{{ route('driver.dashboard') }}" 
                   class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium whitespace-nowrap bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition">
                    <i class="fas fa-truck"></i>
                    <span>Driver</span>
                </a>
                @endif
                @if(setting('features.multi_vendor'))
                    @if(auth()->user()->vendor)
                    <a href="{{ route('vendor.dashboard') }}" 
                       class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium whitespace-nowrap bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition">
                        <i class="fas fa-store"></i>
                        <span>Vendor</span>
                    </a>
                    @endif
                @endif
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 px-4 py-2.5 rounded-full text-sm font-medium whitespace-nowrap bg-red-100 text-red-700 hover:bg-red-200 transition">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Desktop Sidebar --}}
<div class="hidden lg:block lg:col-span-1">
    <div class="bg-white rounded-xl shadow-sm p-6 sticky top-4">
        <div class="text-center mb-6">
            <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user text-3xl text-primary-600"></i>
            </div>
            <h3 class="font-semibold text-gray-900">{{ auth()->user()->name }}</h3>
            <p class="text-sm text-gray-500">{{ auth()->user()->email }}</p>
        </div>
        
        <nav class="space-y-1">
            <a href="{{ route('account.dashboard') }}" class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('account.dashboard') ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:bg-gray-50' }}">
                <i class="fas fa-tachometer-alt w-5"></i>
                <span class="ml-3">Dashboard</span>
            </a>
            <a href="{{ route('account.orders') }}" class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('account.orders*') ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:bg-gray-50' }}">
                <i class="fas fa-shopping-bag w-5"></i>
                <span class="ml-3">My Orders</span>
            </a>
            @module('wishlist')
            <a href="{{ route('account.wishlist') }}" class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('account.wishlist') ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:bg-gray-50' }}">
                <i class="fas fa-heart w-5"></i>
                <span class="ml-3">Wishlist</span>
            </a>
            @endmodule
            <a href="{{ route('account.profile') }}" class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('account.profile') ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:bg-gray-50' }}">
                <i class="fas fa-user-cog w-5"></i>
                <span class="ml-3">Profile</span>
            </a>
            <a href="{{ route('account.password') }}" class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('account.password') ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:bg-gray-50' }}">
                <i class="fas fa-lock w-5"></i>
                <span class="ml-3">Change Password</span>
            </a>
            <a href="{{ route('helpdesk.index') }}" class="flex items-center px-4 py-3 rounded-lg {{ request()->routeIs('helpdesk.*') ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:bg-gray-50' }}">
                <i class="fas fa-headset w-5"></i>
                <span class="ml-3">Support Tickets</span>
            </a>
            @if(auth()->user()->is_driver && setting('features.driver_portal', true))
            <hr class="my-2">
            <a href="{{ route('driver.dashboard') }}" class="flex items-center px-4 py-3 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100">
                <i class="fas fa-truck w-5"></i>
                <span class="ml-3">Driver Portal</span>
            </a>
            @endif
            @if(setting('features.multi_vendor'))
            <hr class="my-2">
            @if(auth()->user()->vendor)
            <a href="{{ route('vendor.dashboard') }}" class="flex items-center px-4 py-3 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100">
                <i class="fas fa-store w-5"></i>
                <span class="ml-3">Vendor Panel</span>
            </a>
            @else
            <a href="{{ route('vendor.register') }}" class="flex items-center px-4 py-3 rounded-lg text-emerald-600 hover:bg-emerald-50">
                <i class="fas fa-store w-5"></i>
                <span class="ml-3">Become a Vendor</span>
            </a>
            @endif
            @endif
            <hr class="my-2">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-50 w-full">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span class="ml-3">Logout</span>
                </button>
            </form>
        </nav>
    </div>
</div>
