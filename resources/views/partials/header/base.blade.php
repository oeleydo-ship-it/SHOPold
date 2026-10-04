{{-- Shared storefront header (top bar, desktop + mobile header, menus). Included by partials/header. --}}
    @php
        $headerStyle = $appSettings['theme']['header_style'] ?? 'default';
        $stickyHeader = $appSettings['theme']['sticky_header'] ?? true;
        $showTopbar = $appSettings['theme']['show_topbar'] ?? true;
        $headerNav = $appSettings['navigation']['header'] ?? [];
        $headerHeight = $appSettings['theme']['header_height'] ?? 'normal';
        $headerSpacing = $appSettings['theme']['header_spacing'] ?? 'normal';
        $headerBgColor = $appSettings['theme']['header_bg_color'] ?? '#ffffff';
        $headerTextColor = $appSettings['theme']['header_text_color'] ?? '#374151';
        $headerHoverColor = $appSettings['theme']['header_hover_color'] ?? '#2563eb';
        
        // Header height classes
        $heightClass = match($headerHeight) {
            'compact' => 'h-14',
            'large' => 'h-20',
            default => 'h-16',
        };
        
        // Logo-menu spacing classes - using gap for RTL support
        $spacingClass = match($headerSpacing) {
            'tight' => 'gap-4',
            'wide' => 'gap-12',
            default => 'gap-8',
        };
    @endphp

    <!-- Top Bar (only for non-mega, non-transparent, non-dark header styles) - Hidden on mobile -->
    @if($showTopbar && !in_array($headerStyle, ['mega', 'transparent', 'dark']))
    @php
        $topbarBgColor = $appSettings['theme']['header_topbar_bg'] ?? '#1f2937';
        $topbarPromoText = $appSettings['theme']['header_promo_text'] ?? '';
        $availableLanguages = $appSettings['available_languages'] ?? ['en' => 'English'];
        $currentLocale = $appSettings['current_locale'] ?? 'en';
        $currentLanguageName = $availableLanguages[$currentLocale] ?? 'English';
        $localizationEnabled = $appSettings['features']['localization_enabled'] ?? true;
        // Hide language/currency in top bar for default header (they're shown in main nav instead)
        $hideTopbarLangCurrency = ($headerStyle === 'default');
        $showLanguageSwitcher = $localizationEnabled && !$hideTopbarLangCurrency;

        $displayCurrency = $appSettings['currency'] ?? 'USD';
        $displayCurrencySymbol = $appSettings['currency_symbol'] ?? '$';
        $multiCurrencyEnabled = $appSettings['features']['multi_currency'] ?? false;
        $enabledCurrencies = $appSettings['enabled_currencies'] ?? [$displayCurrency];
        if (is_string($enabledCurrencies)) {
            $enabledCurrencies = json_decode($enabledCurrencies, true) ?? [$displayCurrency];
        }
        if (!is_array($enabledCurrencies)) {
            $enabledCurrencies = [$displayCurrency];
        }
        $showCurrencySwitcher = $multiCurrencyEnabled && !$hideTopbarLangCurrency;
    @endphp
    <div class="hidden md:block text-white text-sm" style="background-color: {{ $topbarBgColor }};">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-10">
                <div class="flex items-center space-x-4">
                    @if(!empty($appSettings['store_phone']))
                    <span><i class="fas fa-phone-alt mr-2"></i>{{ $appSettings['store_phone'] }}</span>
                    @endif
                    @if(!empty($appSettings['store_email']))
                    <span class="hidden sm:inline"><i class="fas fa-envelope mr-2"></i>{{ $appSettings['store_email'] }}</span>
                    @endif
                </div>
                @if(!empty($topbarPromoText))
                <div class="flex-1 text-center hidden md:block">
                    <span class="text-yellow-400 font-medium">{{ $topbarPromoText }}</span>
                </div>
                @endif
                <div class="flex items-center space-x-3" x-data="{ langOpen: false, currencyOpen: false }">
                    @if($showLanguageSwitcher && count($availableLanguages) > 1)
                    <div class="relative">
                        <button type="button" @click="langOpen = !langOpen" class="flex items-center text-white/90 hover:text-white transition">
                            <i class="fas fa-globe mr-1"></i>
                            <span>{{ $currentLanguageName }}</span>
                            <i class="fas fa-chevron-down text-xs ml-1"></i>
                        </button>
                        <div x-show="langOpen" @click.away="langOpen = false" x-cloak class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg py-1 z-50">
                            @foreach($availableLanguages as $code => $name)
                            <a href="{{ route('locale.switch', $code) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 {{ $currentLocale === $code ? 'bg-primary-50 text-primary-600' : '' }}">
                                {{ $name }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($showLanguageSwitcher && $showCurrencySwitcher)
                    <span class="text-white/50">|</span>
                    @endif

                    @if($showCurrencySwitcher)
                        @if($multiCurrencyEnabled && is_array($enabledCurrencies) && count($enabledCurrencies) > 1)
                        <div class="relative">
                            <button type="button" @click="currencyOpen = !currencyOpen" class="flex items-center text-white/90 hover:text-white transition">
                                <span>{{ $displayCurrency }} ({{ $displayCurrencySymbol }})</span>
                                <i class="fas fa-chevron-down text-xs ml-1"></i>
                            </button>
                            <div x-show="currencyOpen" @click.away="currencyOpen = false" x-cloak class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg py-1 z-50">
                                @foreach($enabledCurrencies as $code)
                                    @php $code = strtoupper((string) $code); @endphp
                                    <a href="{{ route('currency.switch', $code) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 {{ $displayCurrency === $code ? 'bg-primary-50 text-primary-600' : '' }}">
                                        {{ $code }} ({{ currency_symbol($code) }})
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @else
                        <span class="text-white/90">{{ $displayCurrency }} ({{ $displayCurrencySymbol }})</span>
                        @endif
                    @endif

                    @if(!empty($appSettings['social']['facebook']))
                    <a href="{{ $appSettings['social']['facebook'] }}" target="_blank" class="hover:text-primary-400 transition"><i class="fab fa-facebook-f"></i></a>
                    @endif
                    @if(!empty($appSettings['social']['twitter']))
                    <a href="{{ $appSettings['social']['twitter'] }}" target="_blank" class="hover:text-primary-400 transition"><i class="fab fa-twitter"></i></a>
                    @endif
                    @if(!empty($appSettings['social']['instagram']))
                    <a href="{{ $appSettings['social']['instagram'] }}" target="_blank" class="hover:text-primary-400 transition"><i class="fab fa-instagram"></i></a>
                    @endif
                    @if(!empty($appSettings['social']['youtube']))
                    <a href="{{ $appSettings['social']['youtube'] }}" target="_blank" class="hover:text-primary-400 transition"><i class="fab fa-youtube"></i></a>
                    @endif
                    @if(!empty($appSettings['social']['linkedin']))
                    <a href="{{ $appSettings['social']['linkedin'] }}" target="_blank" class="hover:text-primary-400 transition"><i class="fab fa-linkedin"></i></a>
                    @endif
                    @if(!empty($appSettings['social']['tiktok']))
                    <a href="{{ $appSettings['social']['tiktok'] }}" target="_blank" class="hover:text-primary-400 transition"><i class="fab fa-tiktok"></i></a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Header -->
    @php
        // Transparent header only applies on homepage
        $isHomePage = request()->routeIs('home');
        $isTransparentHeader = $headerStyle === 'transparent' && $isHomePage;
        $headerClasses = $isTransparentHeader 
            ? 'absolute top-0 left-0 right-0 z-50 bg-transparent' 
            : 'shadow-sm z-50' . ($stickyHeader ? ' sticky top-0' : '');
        $headerBgStyle = $isTransparentHeader ? '' : "background-color: {$headerBgColor};";
    @endphp
    <header class="{{ $headerClasses }}" 
            style="{{ $headerBgStyle }}"
            x-data="{ mobileMenu: false, searchOpen: false }">
        <style>
            header .header-text { color: {{ $isTransparentHeader ? '#ffffff' : $headerTextColor }}; }
            header .header-icon { color: {{ $isTransparentHeader ? '#ffffff' : $headerTextColor }}; }
            @if(!$isTransparentHeader)
            header a:hover, header button:hover { color: {{ $headerHoverColor }} !important; }
            @endif
            header .nav-link { color: {{ $isTransparentHeader ? 'rgba(255,255,255,0.9)' : $headerTextColor }}; }
            header .nav-link:hover { color: {{ $isTransparentHeader ? '#ffffff' : $headerHoverColor }} !important; }
            header .nav-link.active { color: {{ $headerHoverColor }}; }
        </style>
        
        {{-- ========== MOBILE HEADER (Revibe Style) - Shows only on mobile ========== --}}
        @php
            $availableLanguages = $appSettings['available_languages'] ?? ['en' => 'English'];
            $currentLocale = $appSettings['current_locale'] ?? 'en';
            $currentLanguageName = $availableLanguages[$currentLocale] ?? 'English';
            $localizationEnabled = $appSettings['features']['localization_enabled'] ?? true;
            $showLanguageSwitcher = $localizationEnabled;
            $mobileCart = \App\Models\Cart::getCart();
            $mobilePromoText = $appSettings['theme']['header_promo_text'] ?? '';
            $mobileTopbarBg = $appSettings['theme']['header_topbar_bg'] ?? '#7c3aed';
            
            // Mobile-specific settings from admin
            $mobileShowPromo = setting('products.mobile_show_promo', true);
            $mobileShowSearch = setting('products.mobile_show_search', true);
            $mobileShowLanguage = setting('products.mobile_show_language', true);
        @endphp
        <div class="md:hidden">
            {{-- Mobile Promo Banner with Sliding Animation --}}
            @if($mobileShowPromo && !empty($mobilePromoText))
            <div class="text-white text-xs py-2 font-medium overflow-hidden relative" style="background-color: {{ $mobileTopbarBg }};">
                <div class="mobile-marquee-container">
                    <div class="mobile-marquee-content">
                        <span class="inline-flex items-center px-4">
                            <i class="fas fa-tag mr-2"></i>{{ $mobilePromoText }}
                        </span>
                        <span class="inline-flex items-center px-4">
                            <i class="fas fa-tag mr-2"></i>{{ $mobilePromoText }}
                        </span>
                    </div>
                </div>
            </div>
            <style>
                .mobile-marquee-container {
                    width: 100%;
                    overflow: hidden;
                }
                .mobile-marquee-content {
                    display: inline-flex;
                    white-space: nowrap;
                    animation: marquee-scroll 15s linear infinite;
                }
                .mobile-marquee-content:hover {
                    animation-play-state: paused;
                }
                @keyframes marquee-scroll {
                    0% { transform: translateX(0); }
                    100% { transform: translateX(-50%); }
                }
            </style>
            @endif
            
            <div style="background-color: {{ $headerBgColor }};">
            {{-- Row 1: Hamburger | Logo (Centered) | Icons --}}
            <div class="flex items-center px-4 h-16 relative">
                {{-- Left: Hamburger Menu --}}
                <div class="flex-shrink-0">
                    <button @click="mobileMenu = !mobileMenu" class="p-2 header-icon">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
                
                {{-- Center: Logo (Absolutely positioned for true center) --}}
                <a href="{{ route('home') }}" class="absolute left-1/2 transform -translate-x-1/2 flex items-center justify-center">
                    @if(!empty($appSettings['store_logo']))
                        <img src="{{ Storage::url($appSettings['store_logo']) }}" alt="{{ $appSettings['store_name'] ?? 'Store' }}" class="h-9 w-auto max-w-[140px] object-contain">
                    @else
                        <span class="text-xl font-bold header-text truncate">{{ $appSettings['store_name'] ?? 'Shop' }}</span>
                    @endif
                </a>
                
                {{-- Right: Currency + Icons (pushed to right) --}}
                <div class="flex-shrink-0 flex items-center ml-auto" x-data="{ currencyOpen: false }">
                    {{-- Currency Switcher (Language moved to slide menu) --}}
                    @php
                        $multiCurrencyEnabledMobile = $appSettings['features']['multi_currency'] ?? false;
                        $enabledCurrenciesMobile = $appSettings['enabled_currencies'] ?? [$appSettings['currency'] ?? 'USD'];
                        if (is_string($enabledCurrenciesMobile)) {
                            $enabledCurrenciesMobile = json_decode($enabledCurrenciesMobile, true) ?? [$appSettings['currency'] ?? 'USD'];
                        }
                        if (!is_array($enabledCurrenciesMobile)) {
                            $enabledCurrenciesMobile = [$appSettings['currency'] ?? 'USD'];
                        }
                        $currentCurrencyMobile = $appSettings['currency'] ?? 'USD';
                    @endphp
                    @if($multiCurrencyEnabledMobile && is_array($enabledCurrenciesMobile) && count($enabledCurrenciesMobile) > 1)
                    <div class="relative">
                        <button @click="currencyOpen = !currencyOpen" class="p-2 header-icon text-base flex items-center">
                            <span>{{ strtoupper($currentCurrencyMobile) }}</span>
                            <i class="fas fa-chevron-down text-xs ml-1"></i>
                        </button>
                        <div x-show="currencyOpen" @click.away="currencyOpen = false" x-cloak 
                             class="absolute right-0 mt-2 w-32 bg-white rounded-lg shadow-lg py-1 z-50 border">
                            @foreach($enabledCurrenciesMobile as $code)
                                @php $code = strtoupper((string) $code); @endphp
                                <a href="{{ route('currency.switch', $code) }}" 
                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 {{ strtoupper($currentCurrencyMobile) === $code ? 'bg-primary-50 text-primary-600' : '' }}">
                                    {{ $code }} ({{ currency_symbol($code) }})
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    {{-- User Icon --}}
                    @auth
                        <a href="{{ route('account.dashboard') }}" class="p-2 header-icon text-lg">
                            <i class="fas fa-user"></i>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="p-2 header-icon text-lg">
                            <i class="fas fa-user"></i>
                        </a>
                    @endauth
                    
                    {{-- Wishlist Icon --}}
                    @auth
                    @module('wishlist')
                    <a href="{{ route('account.wishlist') }}" class="p-2 header-icon text-lg">
                        <i class="far fa-heart"></i>
                    </a>
                    @endmodule
                    @endauth
                    
                    {{-- Cart Icon --}}
                    <a href="{{ route('cart.index') }}" class="p-2 header-icon text-lg relative">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count-badge absolute -top-1 -right-1 w-5 h-5 bg-primary-600 text-white text-xs rounded-full flex items-center justify-center {{ $mobileCart->item_count > 0 ? '' : 'hidden' }}">{{ $mobileCart->item_count }}</span>
                    </a>
                </div>
            </div>
            
            {{-- Row 2: Search Bar --}}
            @if($mobileShowSearch)
            <div class="px-4 pb-4">
                <form action="{{ route('shop.index') }}" method="GET">
                    <div class="relative">
                        <input type="text" name="search" placeholder="{{ __('messages.search_for') ?? 'What are you looking for...' }}" value="{{ request('search') }}" 
                               class="w-full h-12 pl-4 pr-14 border border-gray-300 rounded-lg text-base focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <button type="submit" class="absolute right-0 top-0 h-12 w-12 bg-primary-600 text-white rounded-r-lg flex items-center justify-center hover:bg-primary-700 transition">
                            <i class="fas fa-search text-lg"></i>
                        </button>
                    </div>
                </form>
            </div>
            @endif
            </div>
        </div>
        {{-- ========== END MOBILE HEADER ========== --}}
        
        @if($headerStyle === 'centered')
        {{-- Centered Header Style - Desktop Only --}}
        <div class="hidden md:block max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Top row with search and actions --}}
            <div class="flex items-center justify-between h-14 border-b" style="border-color: rgba(0,0,0,0.1);">
                <form action="{{ route('shop.index') }}" method="GET" class="hidden md:flex flex-1 max-w-xs">
                    <div class="relative w-full">
                        <input type="text" name="search" placeholder="Search..." value="{{ request('search') }}" 
                               class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-full text-sm focus:ring-2 focus:ring-primary-500">
                        <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                {{-- Centered Logo --}}
                <a href="{{ route('home') }}" class="flex items-center space-x-2 absolute left-1/2 -translate-x-1/2">
                    @if(!empty($appSettings['store_logo']))
                        <img src="{{ Storage::url($appSettings['store_logo']) }}" alt="{{ $appSettings['store_name'] ?? 'Store' }}" class="h-10 w-auto">
                    @else
                        <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-white text-xl"></i>
                        </div>
                    @endif
                    @if(empty($appSettings['hide_name_in_header']))
                        <span class="text-xl font-bold header-text hidden sm:inline">{{ $appSettings['store_name'] ?? 'LaravelShop' }}</span>
                    @endif
                </a>
                
                {{-- Right actions --}}
                <div class="flex items-center space-x-4">
                    @auth
                        @module('wishlist')
                        <a href="{{ route('account.wishlist') }}" class="p-2 header-icon"><i class="fas fa-heart"></i></a>
                        @endmodule
                    @endauth
                    <a href="{{ route('cart.index') }}" class="p-2 header-icon relative">
                        <i class="fas fa-shopping-cart"></i>
                        @php $cart = \App\Models\Cart::getCart(); @endphp
                        <span class="cart-count-badge absolute -top-1 -right-1 w-5 h-5 bg-primary-600 text-white text-xs rounded-full flex items-center justify-center {{ $cart->item_count > 0 ? '' : 'hidden' }}">{{ $cart->item_count }}</span>
                    </a>
                    @auth
                        <a href="{{ route('account.dashboard') }}" class="p-2 header-icon"><i class="fas fa-user"></i></a>
                    @else
                        <a href="{{ route('login') }}" class="p-2 header-icon"><i class="fas fa-user"></i></a>
                    @endauth
                    <button @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 header-icon"><i class="fas fa-bars"></i></button>
                </div>
            </div>
            
            {{-- Navigation row --}}
            <nav class="hidden lg:flex items-center justify-center {{ $spacingClass }} h-12">
                @foreach($headerNav as $item)
                    @php
                        $isActive = isset($item['route']) && request()->routeIs($item['route'] . '*');
                        // Translate navigation labels based on route
                        $navLabel = $item['label'];
                        $navTranslations = [
                            'home' => __('messages.home'),
                            'shop.index' => __('messages.shop'),
                            'about' => __('messages.about'),
                            'contact' => __('messages.contact'),
                            'blog.index' => __('messages.blog'),
                        ];
                        if (isset($item['route']) && isset($navTranslations[$item['route']])) {
                            $navLabel = $navTranslations[$item['route']];
                        }
                    @endphp
                    <a href="{{ $item['url'] }}" class="nav-link font-medium transition {{ $isActive ? 'active' : '' }}">
                        {{ $navLabel }}
                    </a>
                @endforeach
            </nav>
        </div>
        @elseif($headerStyle === 'minimal')
        {{-- Minimal Header Style - Desktop Only --}}
        <div class="hidden md:block max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between {{ $heightClass }}">
                <a href="{{ route('home') }}" class="flex items-center space-x-3 mr-8">
                    @if(!empty($appSettings['store_logo']))
                        <img src="{{ Storage::url($appSettings['store_logo']) }}" alt="{{ $appSettings['store_name'] ?? 'Store' }}" class="h-10 w-auto">
                    @else
                        <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-white text-xl"></i>
                        </div>
                    @endif
                    @if(empty($appSettings['hide_name_in_header']))
                        <span class="text-xl font-bold header-text">{{ $appSettings['store_name'] ?? 'LaravelShop' }}</span>
                    @endif
                </a>

                <nav class="hidden lg:flex items-center {{ $spacingClass }} ml-4">
                    @foreach($headerNav as $item)
                        @php
                            $isActive = isset($item['route']) && request()->routeIs($item['route'] . '*');
                            $navLabel = $item['label'];
                            $navTranslations = [
                                'home' => __('messages.home'),
                                'shop.index' => __('messages.shop'),
                                'about' => __('messages.about'),
                                'contact' => __('messages.contact'),
                                'blog.index' => __('messages.blog'),
                            ];
                            if (isset($item['route']) && isset($navTranslations[$item['route']])) {
                                $navLabel = $navTranslations[$item['route']];
                            }
                        @endphp
                        <a href="{{ $item['url'] }}" class="nav-link font-medium transition {{ $isActive ? 'active' : '' }}">
                            {{ $navLabel }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center space-x-3">
                    <button @click="searchOpen = !searchOpen" class="p-2 header-icon"><i class="fas fa-search"></i></button>
                    @auth
                        @module('wishlist')
                        <a href="{{ route('account.wishlist') }}" class="p-2 header-icon"><i class="fas fa-heart"></i></a>
                        @endmodule
                    @endauth
                    <a href="{{ route('cart.index') }}" class="p-2 header-icon relative">
                        <i class="fas fa-shopping-cart"></i>
                        @php $cart = \App\Models\Cart::getCart(); @endphp
                        <span class="cart-count-badge absolute -top-1 -right-1 w-5 h-5 bg-primary-600 text-white text-xs rounded-full flex items-center justify-center {{ $cart->item_count > 0 ? '' : 'hidden' }}">{{ $cart->item_count }}</span>
                    </a>
                    @auth
                        <a href="{{ route('account.dashboard') }}" class="p-2 header-icon"><i class="fas fa-user"></i></a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium header-text">{{ __('messages.login') }}</a>
                    @endauth
                    <button @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 header-icon"><i class="fas fa-bars"></i></button>
                </div>
            </div>
            
            {{-- Expandable Search --}}
            <div x-show="searchOpen" x-cloak class="pb-4">
                <form action="{{ route('shop.index') }}" method="GET">
                    <input type="text" name="search" placeholder="Search products..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                </form>
            </div>
        </div>
        @elseif($headerStyle === 'mega')
        {{-- Mega Store Header Style (3-row ecommerce) - Desktop Only --}}
        @php
            $headerPromoText = $appSettings['theme']['header_promo_text'] ?? '';
            $headerCategoriesText = __('messages.browse_all_categories');
            $headerTopbarBg = $appSettings['theme']['header_topbar_bg'] ?? '#1f2937';
            $headerCategoriesBg = $appSettings['theme']['header_categories_bg'] ?? '#2563eb';
            $headerSearchStyle = $appSettings['theme']['header_search_style'] ?? 'simple';
            $showWishlistIcon = $appSettings['theme']['header_show_wishlist'] ?? true;
            $showCompareIcon = $appSettings['theme']['header_show_compare'] ?? true;
            $showAccountText = $appSettings['theme']['header_show_account_text'] ?? true;
            $megaCategories = \App\Models\Category::where('is_active', true)->with('translations')->orderBy('name')->take(12)->get();
        @endphp
        
        {{-- Row 1: Top Info Bar - Desktop Only --}}
        <div class="hidden md:block text-white text-xs" style="background-color: {{ $headerTopbarBg }};">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-9">
                    <div class="flex items-center space-x-4">
                        @if(!empty($appSettings['store_phone']))
                        <span class="flex items-center"><i class="fas fa-headset mr-1.5"></i>{{ __('messages.support_24_7_header') }}</span>
                        @endif
                        @if(!empty($appSettings['store_email']))
                        <span class="hidden sm:flex items-center"><i class="fas fa-envelope mr-1.5"></i>{{ $appSettings['store_email'] }}</span>
                        @endif
                    </div>
                    <div class="flex-1 text-center hidden md:block">
                        <span class="text-yellow-400 font-medium">{{ $headerPromoText }}</span>
                    </div>
                    @php
                        $displayCurrency = $appSettings['currency'] ?? 'USD';
                        $displayCurrencySymbol = $appSettings['currency_symbol'] ?? '$';
                        $multiCurrencyEnabled = $appSettings['features']['multi_currency'] ?? false;
                        $enabledCurrencies = $appSettings['enabled_currencies'] ?? [$displayCurrency];
                        if (is_string($enabledCurrencies)) {
                            $enabledCurrencies = json_decode($enabledCurrencies, true) ?? [$displayCurrency];
                        }
                        if (!is_array($enabledCurrencies)) {
                            $enabledCurrencies = [$displayCurrency];
                        }
                        $availableLanguages = $appSettings['available_languages'] ?? ['en' => 'English'];
                        $currentLocale = $appSettings['current_locale'] ?? 'en';
                        $currentLanguageName = $availableLanguages[$currentLocale] ?? 'English';
                        $localizationEnabled = $appSettings['features']['localization_enabled'] ?? true;
                        $showLanguageSwitcher = $localizationEnabled;
                        $showCurrencySwitcher = $multiCurrencyEnabled;
                    @endphp
                    <div class="flex items-center space-x-3" x-data="{ langOpen: false, currencyOpen: false }">
                        @if(($appSettings['theme']['enable_dark_mode'] ?? false))
                        <button type="button" onclick="window.toggleDarkMode && window.toggleDarkMode()" class="hidden lg:flex items-center text-gray-300 hover:text-white transition" title="Toggle Dark Mode">
                            <i class="fas fa-moon"></i>
                        </button>
                        @endif
                        @if($showLanguageSwitcher && count($availableLanguages) > 1)
                        <div class="relative">
                            <button @click="langOpen = !langOpen" class="hidden lg:flex items-center text-gray-300 hover:text-white transition">
                                <i class="fas fa-globe mr-1"></i>
                                <span>{{ $currentLanguageName }}</span>
                                <i class="fas fa-chevron-down text-xs ml-1"></i>
                            </button>
                            <div x-show="langOpen" @click.away="langOpen = false" x-cloak 
                                 class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg py-1 z-50">
                                @foreach($availableLanguages as $code => $name)
                                <a href="{{ route('locale.switch', $code) }}" 
                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 {{ $currentLocale === $code ? 'bg-primary-50 text-primary-600' : '' }}">
                                    {{ $name }}
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @elseif($showLanguageSwitcher)
                        <span class="hidden lg:inline text-gray-300">{{ $currentLanguageName }}</span>
                        @endif
                        @if($showLanguageSwitcher && $showCurrencySwitcher)
                        <span class="hidden lg:inline text-gray-300">|</span>
                        @endif
                        @if($showCurrencySwitcher)
                            @if($multiCurrencyEnabled && is_array($enabledCurrencies) && count($enabledCurrencies) > 1)
                                <div class="relative">
                                    <button type="button" @click="currencyOpen = !currencyOpen" class="flex items-center text-gray-300 hover:text-white transition">
                                        <span>{{ $displayCurrency }} ({{ $displayCurrencySymbol }})</span>
                                        <i class="fas fa-chevron-down text-xs ml-1"></i>
                                    </button>
                                    <div x-show="currencyOpen" @click.away="currencyOpen = false" x-cloak class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg py-1 z-50">
                                        @foreach($enabledCurrencies as $code)
                                            @php $code = strtoupper((string) $code); @endphp
                                            <a href="{{ route('currency.switch', $code) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 {{ $displayCurrency === $code ? 'bg-primary-50 text-primary-600' : '' }}">
                                                {{ $code }} ({{ currency_symbol($code) }})
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-300">{{ $displayCurrency }} ({{ $displayCurrencySymbol }})</span>
                            @endif
                        @endif
                        @auth
                        <a href="{{ route('account.dashboard') }}" class="flex items-center hover:text-primary-400 transition">
                            <i class="fas fa-user mr-1"></i>{{ __('messages.my_account') }}
                        </a>
                        @else
                        <a href="{{ route('login') }}" class="flex items-center hover:text-primary-400 transition">
                            <i class="fas fa-user mr-1"></i>{{ __('messages.my_account') }}
                        </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Row 2: Logo + Search + Icons - Desktop Only --}}
        <div class="hidden md:block bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    {{-- Logo --}}
                    <a href="{{ route('home') }}" class="flex items-center space-x-3 flex-shrink-0">
                        @if(!empty($appSettings['store_logo']))
                            <img src="{{ Storage::url($appSettings['store_logo']) }}" alt="{{ $appSettings['store_name'] ?? 'Store' }}" class="h-12 w-auto">
                        @else
                            <div class="w-12 h-12 bg-primary-600 rounded-xl flex items-center justify-center">
                                <i class="fas fa-shopping-bag text-white text-2xl"></i>
                            </div>
                        @endif
                        @if(empty($appSettings['hide_name_in_header']))
                            <span class="text-2xl font-bold text-gray-900 hidden sm:inline">{{ $appSettings['store_name'] ?? 'LaravelShop' }}</span>
                        @endif
                    </a>
                    
                    {{-- Search Bar --}}
                    <div class="hidden md:flex flex-1 max-w-2xl mx-8">
                        <form action="{{ route('shop.index') }}" method="GET" class="w-full">
                            <div class="flex">
                                @if($headerSearchStyle === 'category')
                                <div class="relative" x-data="{ open: false }">
                                    <button type="button" @click="open = !open" class="h-12 px-4 bg-gray-100 border border-r-0 border-gray-300 rounded-l-lg flex items-center space-x-2 hover:bg-gray-200 transition">
                                        <span class="text-sm text-gray-700">{{ __('messages.all_categories') }}</span>
                                        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                                    </button>
                                    <div x-show="open" @click.away="open = false" x-cloak class="absolute top-full left-0 mt-1 w-56 bg-white rounded-lg shadow-xl border py-2 z-50">
                                        @foreach($megaCategories as $cat)
                                        <a href="{{ route('shop.category', $cat->slug) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600">{{ $cat->name }}</a>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                <div class="relative flex-1">
                                    <input type="text" name="search" placeholder="{{ __('messages.search_for') }}" value="{{ request('search') }}" 
                                           class="w-full h-12 pl-4 pr-12 border border-gray-300 {{ $headerSearchStyle === 'category' ? '' : 'rounded-l-lg' }} focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <button type="submit" class="h-12 px-6 text-white rounded-r-lg transition flex items-center" style="background-color: {{ $headerCategoriesBg }};">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    {{-- Right Icons --}}
                    <div class="flex items-center space-x-4 lg:space-x-6">
                        @if(($appSettings['theme']['enable_dark_mode'] ?? false))
                        <button type="button" onclick="window.toggleDarkMode && window.toggleDarkMode()" class="hidden lg:flex items-center text-gray-700 hover:text-primary-600 transition" title="Toggle Dark Mode">
                            <i class="fas fa-moon"></i>
                        </button>
                        @endif
                        @php
                            $displayCurrency = $appSettings['currency'] ?? 'USD';
                            $displayCurrencySymbol = $appSettings['currency_symbol'] ?? '$';
                            $multiCurrencyEnabled = $appSettings['features']['multi_currency'] ?? false;
                            $enabledCurrencies = $appSettings['enabled_currencies'] ?? [$displayCurrency];
                            if (is_string($enabledCurrencies)) {
                                $enabledCurrencies = json_decode($enabledCurrencies, true) ?? [$displayCurrency];
                            }
                            if (!is_array($enabledCurrencies)) {
                                $enabledCurrencies = [$displayCurrency];
                            }
                        @endphp
                        @if($multiCurrencyEnabled && is_array($enabledCurrencies) && count($enabledCurrencies) > 1)
                        <div class="relative" x-data="{ currencyOpen: false }">
                            <button type="button" @click="currencyOpen = !currencyOpen" class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 transition">
                                <span class="text-sm font-medium">{{ $displayCurrency }} ({{ $displayCurrencySymbol }})</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div x-show="currencyOpen" @click.away="currencyOpen = false" x-cloak class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg py-1 z-50 border">
                                @foreach($enabledCurrencies as $code)
                                    @php $code = strtoupper((string) $code); @endphp
                                    <a href="{{ route('currency.switch', $code) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 {{ $displayCurrency === $code ? 'bg-primary-50 text-primary-600' : '' }}">
                                        {{ $code }} ({{ currency_symbol($code) }})
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($showWishlistIcon)
                        @module('wishlist')
                        <a href="{{ route('account.wishlist') }}" class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 transition">
                            <div class="relative">
                                <i class="far fa-heart text-xl"></i>
                                <span class="absolute -top-2 -right-2 w-5 h-5 bg-primary-600 text-white text-xs rounded-full flex items-center justify-center">0</span>
                            </div>
                            <span class="hidden lg:inline text-sm font-medium">Wishlist</span>
                        </a>
                        @endmodule
                        @endif
                        
                        @if($showCompareIcon)
                        @module('compare')
                        <a href="#" class="hidden lg:flex items-center space-x-2 text-gray-700 hover:text-primary-600 transition">
                            <div class="relative">
                                <i class="fas fa-exchange-alt text-xl"></i>
                                <span class="absolute -top-2 -right-2 w-5 h-5 bg-primary-600 text-white text-xs rounded-full flex items-center justify-center">0</span>
                            </div>
                            <span class="text-sm font-medium">{{ __('messages.compare') }}</span>
                        </a>
                        @endmodule
                        @endif
                        
                        <a href="{{ route('cart.index') }}" class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 transition">
                            <div class="relative">
                                <i class="fas fa-shopping-cart text-xl"></i>
                                @php $cart = \App\Models\Cart::getCart(); @endphp
                                <span class="cart-count-badge absolute -top-2 -right-2 w-5 h-5 bg-primary-600 text-white text-xs rounded-full flex items-center justify-center {{ $cart->item_count > 0 ? '' : 'hidden' }}">{{ $cart->item_count }}</span>
                            </div>
                            <span class="hidden lg:inline text-sm font-medium">{{ __('messages.cart') }}</span>
                        </a>
                        
                        <button @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 text-gray-600">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Row 3: Navigation Bar with Categories Dropdown --}}
        @php
            $menuBgColor = $appSettings['theme']['header_menu_bg'] ?? '#ffffff';
            $menuTextColor = $appSettings['theme']['header_menu_text'] ?? '#374151';
            $menuHoverColor = $appSettings['theme']['header_menu_hover'] ?? '#2563eb';
            $menuActiveColor = $appSettings['theme']['header_menu_active'] ?? '#2563eb';
        @endphp
        <div class="shadow-sm hidden lg:block" style="background-color: {{ $menuBgColor }};">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center h-12">
                    {{-- Categories Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="h-12 px-6 text-white font-medium flex items-center space-x-2 transition" style="background-color: {{ $headerCategoriesBg }};">
                            <i class="fas fa-bars"></i>
                            <span>{{ $headerCategoriesText }}</span>
                            <i class="fas fa-chevron-down text-xs ml-2"></i>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="absolute top-full left-0 w-64 bg-white rounded-b-lg shadow-xl border-t-0 py-2 z-50">
                            @foreach($megaCategories as $cat)
                            <a href="{{ route('shop.category', $cat->slug) }}" class="flex items-center px-4 py-2.5 text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition">
                                @if($cat->image)
                                <img src="{{ asset('storage/' . $cat->image) }}" class="w-6 h-6 rounded mr-3 object-cover">
                                @else
                                <i class="fas fa-folder text-gray-400 mr-3"></i>
                                @endif
                                <span>{{ $cat->translated_name ?? $cat->name }}</span>
                            </a>
                            @endforeach
                            <hr class="my-2">
                            <a href="{{ route('shop.index') }}" class="flex items-center px-4 py-2.5 text-primary-600 font-medium hover:bg-primary-50 transition">
                                <i class="fas fa-th-large mr-3"></i>
                                <span>{{ __('messages.view_all_categories') }}</span>
                            </a>
                        </div>
                    </div>
                    
                    {{-- Main Navigation --}}
                    <nav class="flex items-center {{ $spacingClass }} ml-8">
                        @foreach($headerNav as $item)
                            @php
                                $isActive = isset($item['route']) && request()->routeIs($item['route'] . '*');
                                $navLabel = $item['label'];
                                $navTranslations = [
                                    'home' => __('messages.home'),
                                    'shop.index' => __('messages.shop'),
                                    'about' => __('messages.about'),
                                    'contact' => __('messages.contact'),
                                    'blog.index' => __('messages.blog'),
                                ];
                                if (isset($item['route']) && isset($navTranslations[$item['route']])) {
                                    $navLabel = $navTranslations[$item['route']];
                                }
                            @endphp
                            <a href="{{ $item['url'] }}" class="menu-link font-medium transition py-3" 
                               style="color: {{ $isActive ? $menuActiveColor : $menuTextColor }};"
                               onmouseover="this.style.color='{{ $menuHoverColor }}'"
                               onmouseout="this.style.color='{{ $isActive ? $menuActiveColor : $menuTextColor }}'">
                                {{ $navLabel }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </div>
        </div>
        
        @elseif($headerStyle === 'transparent' && $isHomePage)
        {{-- Transparent Header Style - Overlays Hero Section (Homepage Only) --}}
        @php
            $transparentPromoText = $appSettings['theme']['header_promo_text'] ?? '';
            $transparentTopbarBg = $appSettings['theme']['header_topbar_bg'] ?? '#1f2937';
        @endphp
        
        {{-- Optional Top Bar --}}
        @if($showTopbar && !empty($transparentPromoText))
        <div class="text-white text-xs" style="background-color: {{ $transparentTopbarBg }};">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-center h-9">
                    <span class="text-yellow-400 font-medium"><i class="fas fa-tag mr-2"></i>{{ $transparentPromoText }}</span>
                </div>
            </div>
        </div>
        @endif
        
        {{-- Main Transparent Header - Desktop Only --}}
        <div class="hidden md:block max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between {{ $heightClass }}">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center space-x-3">
                    @if(!empty($appSettings['store_logo']))
                        <img src="{{ Storage::url($appSettings['store_logo']) }}" alt="{{ $appSettings['store_name'] ?? 'Store' }}" class="h-10 w-auto brightness-0 invert">
                    @else
                        <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center border border-white/30">
                            <i class="fas fa-shopping-bag text-white text-xl"></i>
                        </div>
                    @endif
                    @if(empty($appSettings['hide_name_in_header']))
                        <span class="text-xl font-bold text-white">{{ $appSettings['store_name'] ?? 'LaravelShop' }}</span>
                    @endif
                </a>

                {{-- Desktop Navigation --}}
                <nav class="hidden lg:flex items-center {{ $spacingClass }}">
                    @foreach($headerNav as $item)
                        @php
                            $isActive = isset($item['route']) && request()->routeIs($item['route'] . '*');
                            $navLabel = $item['label'];
                            $navTranslations = [
                                'home' => __('messages.home'),
                                'shop.index' => __('messages.shop'),
                                'about' => __('messages.about'),
                                'contact' => __('messages.contact'),
                                'blog.index' => __('messages.blog'),
                            ];
                            if (isset($item['route']) && isset($navTranslations[$item['route']])) {
                                $navLabel = $navTranslations[$item['route']];
                            }
                        @endphp
                        <a href="{{ $item['url'] }}" class="text-white/90 hover:text-white font-medium transition {{ $isActive ? 'text-white' : '' }}">
                            {{ $navLabel }}
                        </a>
                    @endforeach
                </nav>

                {{-- Right Actions --}}
                <div class="flex items-center space-x-4">
                    <button @click="searchOpen = !searchOpen" class="p-2 text-white/90 hover:text-white transition">
                        <i class="fas fa-search text-lg"></i>
                    </button>

                    @auth
                        @module('wishlist')
                        <a href="{{ route('account.wishlist') }}" class="p-2 text-white/90 hover:text-white transition relative">
                            <i class="fas fa-heart text-lg"></i>
                        </a>
                        @endmodule
                    @endauth

                    <a href="{{ route('cart.index') }}" class="p-2 text-white/90 hover:text-white transition relative">
                        <i class="fas fa-shopping-cart text-lg"></i>
                        @php $cart = \App\Models\Cart::getCart(); @endphp
                        <span class="cart-count-badge absolute -top-1 -right-1 w-5 h-5 bg-primary-600 text-white text-xs rounded-full flex items-center justify-center {{ $cart->item_count > 0 ? '' : 'hidden' }}">{{ $cart->item_count }}</span>
                    </a>

                    @auth
                        <a href="{{ route('account.dashboard') }}" class="p-2 text-white/90 hover:text-white transition">
                            <i class="fas fa-user text-lg"></i>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2 border border-white/50 text-white rounded-lg hover:bg-white/10 transition">
                            {{ __('messages.login') }}
                        </a>
                    @endauth

                    <button @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 text-white">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>

            {{-- Expandable Search --}}
            <div x-show="searchOpen" x-cloak class="pb-4">
                <form action="{{ route('shop.index') }}" method="GET">
                    <input type="text" name="search" placeholder="Search products..." 
                           class="w-full px-4 py-2 bg-white/10 backdrop-blur-sm border border-white/30 rounded-lg text-white placeholder-white/60 focus:ring-2 focus:ring-white/50 focus:bg-white/20">
                </form>
            </div>
        </div>
        @elseif($headerStyle === 'dark')
        {{-- Dark Header Style - Desktop Only --}}
        @php
            $darkPromoText = $appSettings['theme']['header_promo_text'] ?? '';
            $darkTopbarBg = $appSettings['theme']['header_topbar_bg'] ?? '#111827';
        @endphp
        
        {{-- Top Bar - Desktop Only --}}
        @if($showTopbar)
        <div class="hidden md:block text-gray-300 text-xs" style="background-color: {{ $darkTopbarBg }};">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-9">
                    <div class="flex items-center space-x-4">
                        @if(!empty($appSettings['store_phone']))
                        <span class="flex items-center"><i class="fas fa-phone-alt mr-1.5"></i>{{ $appSettings['store_phone'] }}</span>
                        @endif
                        @if(!empty($appSettings['store_email']))
                        <span class="hidden sm:flex items-center"><i class="fas fa-envelope mr-1.5"></i>{{ $appSettings['store_email'] }}</span>
                        @endif
                    </div>
                    @if(!empty($darkPromoText))
                    <div class="flex-1 text-center hidden md:block">
                        <span class="text-yellow-400 font-medium">{{ $darkPromoText }}</span>
                    </div>
                    @endif
                    @php
                        $displayCurrency = $appSettings['currency'] ?? 'USD';
                        $displayCurrencySymbol = $appSettings['currency_symbol'] ?? '$';
                        $multiCurrencyEnabled = $appSettings['features']['multi_currency'] ?? false;
                        $enabledCurrencies = $appSettings['enabled_currencies'] ?? [$displayCurrency];
                        if (is_string($enabledCurrencies)) {
                            $enabledCurrencies = json_decode($enabledCurrencies, true) ?? [$displayCurrency];
                        }
                        if (!is_array($enabledCurrencies)) {
                            $enabledCurrencies = [$displayCurrency];
                        }

                        $availableLanguages = $appSettings['available_languages'] ?? ['en' => 'English'];
                        $currentLocale = $appSettings['current_locale'] ?? 'en';
                        $currentLanguageName = $availableLanguages[$currentLocale] ?? 'English';
                        $localizationEnabled = $appSettings['features']['localization_enabled'] ?? true;
                        $showLanguageSwitcher = $localizationEnabled;
                        $showCurrencySwitcher = $multiCurrencyEnabled;
                    @endphp
                    <div class="flex items-center space-x-3" x-data="{ langOpen: false, currencyOpen: false }">
                        @if($showLanguageSwitcher && count($availableLanguages) > 1)
                        <div class="relative">
                            <button type="button" @click="langOpen = !langOpen" class="flex items-center hover:text-white transition">
                                <i class="fas fa-globe mr-1"></i>
                                <span>{{ $currentLanguageName }}</span>
                                <i class="fas fa-chevron-down text-xs ml-1"></i>
                            </button>
                            <div x-show="langOpen" @click.away="langOpen = false" x-cloak class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg py-1 z-50">
                                @foreach($availableLanguages as $code => $name)
                                <a href="{{ route('locale.switch', $code) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 {{ $currentLocale === $code ? 'bg-primary-50 text-primary-600' : '' }}">
                                    {{ $name }}
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($showLanguageSwitcher && $showCurrencySwitcher)
                        <span class="text-white/50">|</span>
                        @endif

                        @if($showCurrencySwitcher)
                            @if($multiCurrencyEnabled && is_array($enabledCurrencies) && count($enabledCurrencies) > 1)
                            <div class="relative">
                                <button type="button" @click="currencyOpen = !currencyOpen" class="flex items-center hover:text-white transition">
                                    <span>{{ $displayCurrency }} ({{ $displayCurrencySymbol }})</span>
                                    <i class="fas fa-chevron-down text-xs ml-1"></i>
                                </button>
                                <div x-show="currencyOpen" @click.away="currencyOpen = false" x-cloak class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg py-1 z-50">
                                    @foreach($enabledCurrencies as $code)
                                        @php $code = strtoupper((string) $code); @endphp
                                        <a href="{{ route('currency.switch', $code) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 {{ $displayCurrency === $code ? 'bg-primary-50 text-primary-600' : '' }}">
                                            {{ $code }} ({{ currency_symbol($code) }})
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                            @else
                            <span>{{ $displayCurrency }} ({{ $displayCurrencySymbol }})</span>
                            @endif
                        @endif

                        @if(!empty($appSettings['social']['facebook']))
                        <a href="{{ $appSettings['social']['facebook'] }}" target="_blank" class="hover:text-white transition"><i class="fab fa-facebook-f"></i></a>
                        @endif
                        @if(!empty($appSettings['social']['instagram']))
                        <a href="{{ $appSettings['social']['instagram'] }}" target="_blank" class="hover:text-white transition"><i class="fab fa-instagram"></i></a>
                        @endif
                        @if(!empty($appSettings['social']['twitter']))
                        <a href="{{ $appSettings['social']['twitter'] }}" target="_blank" class="hover:text-white transition"><i class="fab fa-twitter"></i></a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        {{-- Main Dark Header - Desktop Only --}}
        <div class="hidden md:block bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between {{ $heightClass }}">
                    {{-- Logo --}}
                    <a href="{{ route('home') }}" class="flex items-center space-x-3">
                        @if(!empty($appSettings['store_logo']))
                            <img src="{{ Storage::url($appSettings['store_logo']) }}" alt="{{ $appSettings['store_name'] ?? 'Store' }}" class="h-10 w-auto brightness-0 invert">
                        @else
                            <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shopping-bag text-white text-xl"></i>
                            </div>
                        @endif
                        @if(empty($appSettings['hide_name_in_header']))
                            <span class="text-xl font-bold text-white">{{ $appSettings['store_name'] ?? 'LaravelShop' }}</span>
                        @endif
                    </a>

                    {{-- Desktop Navigation --}}
                    <nav class="hidden lg:flex items-center {{ $spacingClass }}">
                        @foreach($headerNav as $item)
                            @php
                                $isActive = isset($item['route']) && request()->routeIs($item['route'] . '*');
                                $navLabel = $item['label'];
                                $navTranslations = [
                                    'home' => __('messages.home'),
                                    'shop.index' => __('messages.shop'),
                                    'about' => __('messages.about'),
                                    'contact' => __('messages.contact'),
                                    'blog.index' => __('messages.blog'),
                                ];
                                if (isset($item['route']) && isset($navTranslations[$item['route']])) {
                                    $navLabel = $navTranslations[$item['route']];
                                }
                            @endphp
                            <a href="{{ $item['url'] }}" class="text-gray-300 hover:text-white font-medium transition {{ $isActive ? 'text-white' : '' }}">
                                {{ $navLabel }}
                            </a>
                        @endforeach
                    </nav>

                    {{-- Search Bar --}}
                    <div class="hidden md:flex flex-1 max-w-md mx-8">
                        <form action="{{ route('shop.index') }}" method="GET" class="w-full">
                            <div class="relative">
                                <input type="text" name="search" placeholder="Search products..." value="{{ request('search') }}" 
                                       class="w-full pl-4 pr-10 py-2 bg-gray-800 border border-gray-700 rounded-full text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Right Actions --}}
                    <div class="flex items-center space-x-4">
                        <button @click="searchOpen = !searchOpen" class="md:hidden p-2 text-gray-300 hover:text-white transition">
                            <i class="fas fa-search text-lg"></i>
                        </button>

                        @auth
                            @module('wishlist')
                            <a href="{{ route('account.wishlist') }}" class="p-2 text-gray-300 hover:text-white transition relative">
                                <i class="fas fa-heart text-lg"></i>
                            </a>
                            @endmodule
                        @endauth

                        <a href="{{ route('cart.index') }}" class="p-2 text-gray-300 hover:text-white transition relative">
                            <i class="fas fa-shopping-cart text-lg"></i>
                            @php $cart = \App\Models\Cart::getCart(); @endphp
                            <span class="cart-count-badge absolute -top-1 -right-1 w-5 h-5 bg-primary-600 text-white text-xs rounded-full flex items-center justify-center {{ $cart->item_count > 0 ? '' : 'hidden' }}">{{ $cart->item_count }}</span>
                        </a>

                        @auth
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center space-x-2 text-gray-300 hover:text-white">
                                    <div class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-gray-300"></i>
                                    </div>
                                    <span class="hidden sm:inline font-medium">{{ auth()->user()->name }}</span>
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </button>
                                <div x-show="open" @click.away="open = false" x-cloak
                                     class="absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-lg border border-gray-700 py-2 z-50">
                                    <a href="{{ route('account.dashboard') }}" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">
                                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                    </a>
                                    <a href="{{ route('account.orders') }}" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">
                                        <i class="fas fa-shopping-bag mr-2"></i>My Orders
                                    </a>
                                    <a href="{{ route('account.profile') }}" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">
                                        <i class="fas fa-user-cog mr-2"></i>Profile
                                    </a>
                                    @if(auth()->user()->is_admin)
                                        <hr class="my-2 border-gray-700">
                                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">
                                            <i class="fas fa-cog mr-2"></i>Admin Panel
                                        </a>
                                    @endif
                                    @if(auth()->user()->is_driver && setting('features.driver_portal', true))
                                        <hr class="my-2 border-gray-700">
                                        <a href="{{ route('driver.dashboard') }}" class="block px-4 py-2 text-emerald-400 hover:bg-gray-700">
                                            <i class="fas fa-truck mr-2"></i>Driver Portal
                                        </a>
                                    @endif
                                    @if(setting('features.multi_vendor'))
                                        <hr class="my-2 border-gray-700">
                                        @if(auth()->user()->vendor)
                                        <a href="{{ route('vendor.dashboard') }}" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">
                                            <i class="fas fa-store mr-2"></i>Vendor Panel
                                        </a>
                                        @else
                                        <a href="{{ route('vendor.register') }}" class="block px-4 py-2 text-emerald-400 hover:bg-gray-700">
                                            <i class="fas fa-store mr-2"></i>Become a Vendor
                                        </a>
                                        @endif
                                    @endif
                                    <hr class="my-2 border-gray-700">
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white">
                                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2 border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition">
                                {{ __('messages.login') }}
                            </a>
                            @if(setting('system.allow_registration', true))
                            <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                                {{ __('messages.register') }}
                            </a>
                            @endif
                        @endauth

                        <button @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 text-gray-300 hover:text-white">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </div>

                {{-- Mobile Search --}}
                <div x-show="searchOpen" x-cloak class="md:hidden pb-4">
                    <form action="{{ route('shop.index') }}" method="GET">
                        <input type="text" name="search" placeholder="Search products..." 
                               class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500">
                    </form>
                </div>
            </div>
        </div>
        @else
        {{-- Default Header Style - Desktop Only --}}
        @php
            $defaultAvailableLanguages = $appSettings['available_languages'] ?? ['en' => 'English'];
            $defaultCurrentLocale = $appSettings['current_locale'] ?? 'en';
            $defaultCurrentLanguageName = $defaultAvailableLanguages[$defaultCurrentLocale] ?? 'English';
            $defaultLocalizationEnabled = $appSettings['features']['localization_enabled'] ?? true;
            $defaultShowLanguageSwitcher = $defaultLocalizationEnabled && count($defaultAvailableLanguages) > 1;
            
            $defaultDisplayCurrency = $appSettings['currency'] ?? 'USD';
            $defaultDisplayCurrencySymbol = $appSettings['currency_symbol'] ?? '$';
            $defaultMultiCurrencyEnabled = $appSettings['features']['multi_currency'] ?? false;
            $defaultEnabledCurrencies = $appSettings['enabled_currencies'] ?? [$defaultDisplayCurrency];
            if (is_string($defaultEnabledCurrencies)) {
                $defaultEnabledCurrencies = json_decode($defaultEnabledCurrencies, true) ?? [$defaultDisplayCurrency];
            }
            if (!is_array($defaultEnabledCurrencies)) {
                $defaultEnabledCurrencies = [$defaultDisplayCurrency];
            }
            $defaultShowCurrencySwitcher = $defaultMultiCurrencyEnabled && count($defaultEnabledCurrencies) > 1;
            
        @endphp
        <div class="hidden md:block max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between {{ $heightClass }}">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center space-x-3 mr-8">
                    @if(!empty($appSettings['store_logo']))
                        <img src="{{ Storage::url($appSettings['store_logo']) }}" alt="{{ $appSettings['store_name'] ?? 'Store' }}" class="h-10 w-auto">
                    @else
                        <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-white text-xl"></i>
                        </div>
                    @endif
                    @if(empty($appSettings['hide_name_in_header']))
                        <span class="text-xl font-bold header-text">{{ $appSettings['store_name'] ?? 'LaravelShop' }}</span>
                    @endif
                </a>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center {{ $spacingClass }} ml-4">
                    @foreach($headerNav as $item)
                        @php
                            $isActive = isset($item['route']) && request()->routeIs($item['route'] . '*');
                            $navLabel = $item['label'];
                            $navTranslations = [
                                'home' => __('messages.home'),
                                'shop.index' => __('messages.shop'),
                                'about' => __('messages.about'),
                                'contact' => __('messages.contact'),
                                'blog.index' => __('messages.blog'),
                            ];
                            if (isset($item['route']) && isset($navTranslations[$item['route']])) {
                                $navLabel = $navTranslations[$item['route']];
                            }
                        @endphp
                        <a href="{{ $item['url'] }}" class="nav-link font-medium transition {{ $isActive ? 'active' : '' }}">
                            {{ $navLabel }}
                        </a>
                    @endforeach
                </nav>

                <!-- Search Bar -->
                <div class="hidden md:flex flex-1 max-w-md mx-8">
                    <form action="{{ route('shop.index') }}" method="GET" class="w-full">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search products..." value="{{ request('search') }}" 
                                   class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-full focus:ring-2 focus:ring-primary-500 focus:border-transparent transition">
                            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center space-x-3" x-data="{ defaultLangOpen: false, defaultCurrencyOpen: false }">
                    <!-- Language Switcher -->
                    @if($defaultShowLanguageSwitcher)
                    <div class="relative">
                        <button type="button" @click="defaultLangOpen = !defaultLangOpen" 
                                class="flex items-center px-3 py-1.5 bg-gray-100 hover:bg-primary-50 text-gray-700 hover:text-primary-600 rounded-lg transition text-sm font-medium border border-gray-200">
                            <i class="fas fa-globe mr-2 text-primary-500"></i>
                            <span>{{ $defaultCurrentLanguageName }}</span>
                            <i class="fas fa-chevron-down text-xs ml-2 text-gray-400"></i>
                        </button>
                        <div x-show="defaultLangOpen" @click.away="defaultLangOpen = false" x-cloak 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="absolute right-0 mt-2 w-44 bg-white rounded-lg shadow-xl border py-1 z-50">
                            @foreach($defaultAvailableLanguages as $code => $name)
                            <a href="{{ route('locale.switch', $code) }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 {{ $defaultCurrentLocale === $code ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                                @if($defaultCurrentLocale === $code)<i class="fas fa-check mr-2 text-primary-500"></i>@else<span class="w-5 mr-2"></span>@endif
                                {{ $name }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Currency Switcher -->
                    @if($defaultShowCurrencySwitcher)
                    <div class="relative">
                        <button type="button" @click="defaultCurrencyOpen = !defaultCurrencyOpen" 
                                class="flex items-center px-3 py-1.5 bg-gray-100 hover:bg-primary-50 text-gray-700 hover:text-primary-600 rounded-lg transition text-sm font-medium border border-gray-200">
                            <span class="text-primary-500 font-semibold mr-1">{{ currency_symbol($defaultDisplayCurrency) }}</span>
                            <span>{{ $defaultDisplayCurrency }}</span>
                            <i class="fas fa-chevron-down text-xs ml-2 text-gray-400"></i>
                        </button>
                        <div x-show="defaultCurrencyOpen" @click.away="defaultCurrencyOpen = false" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform -translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-xl border py-1 z-50">
                            @foreach($defaultEnabledCurrencies as $currCode)
                            @php $currCode = strtoupper((string) $currCode); @endphp
                            <a href="{{ route('currency.switch', $currCode) }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 {{ $defaultDisplayCurrency === $currCode ? 'bg-primary-50 text-primary-600 font-medium' : '' }}">
                                @if($defaultDisplayCurrency === $currCode)<i class="fas fa-check mr-2 text-primary-500"></i>@else<span class="w-5 mr-2"></span>@endif
                                <span class="font-semibold mr-1">{{ currency_symbol($currCode) }}</span> {{ $currCode }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($defaultShowLanguageSwitcher || $defaultShowCurrencySwitcher)
                    <span class="text-gray-200 mx-1">|</span>
                    @endif
                    <button @click="searchOpen = !searchOpen" class="md:hidden p-2 header-icon">
                        <i class="fas fa-search text-lg"></i>
                    </button>

                    @auth
                        @module('wishlist')
                        <a href="{{ route('account.wishlist') }}" class="p-2 header-icon relative">
                            <i class="fas fa-heart text-lg"></i>
                        </a>
                        @endmodule
                    @endauth

                    <a href="{{ route('cart.index') }}" class="p-2 header-icon relative">
                        <i class="fas fa-shopping-cart text-lg"></i>
                        @php $cart = \App\Models\Cart::getCart(); @endphp
                        <span class="cart-count-badge absolute -top-1 -right-1 w-5 h-5 bg-primary-600 text-white text-xs rounded-full flex items-center justify-center {{ $cart->item_count > 0 ? '' : 'hidden' }}">{{ $cart->item_count }}</span>
                    </a>

                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 header-text">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-primary-600"></i>
                                </div>
                                <span class="hidden sm:inline font-medium">{{ auth()->user()->name }}</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border py-2 z-50">
                                <a href="{{ route('account.dashboard') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                </a>
                                <a href="{{ route('account.orders') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-shopping-bag mr-2"></i>My Orders
                                </a>
                                <a href="{{ route('account.profile') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user-cog mr-2"></i>Profile
                                </a>
                                @if(auth()->user()->is_admin)
                                    <hr class="my-2">
                                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-cog mr-2"></i>Admin Panel
                                    </a>
                                @endif
                                @if(auth()->user()->is_driver && setting('features.driver_portal', true))
                                    <hr class="my-2">
                                    <a href="{{ route('driver.dashboard') }}" class="block px-4 py-2 text-emerald-600 hover:bg-emerald-50">
                                        <i class="fas fa-truck mr-2"></i>Driver Portal
                                    </a>
                                @endif
                                @if(setting('features.multi_vendor'))
                                    <hr class="my-2">
                                    @if(auth()->user()->vendor)
                                    <a href="{{ route('vendor.dashboard') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-store mr-2"></i>Vendor Panel
                                    </a>
                                    @else
                                    <a href="{{ route('vendor.register') }}" class="block px-4 py-2 text-emerald-600 hover:bg-emerald-50">
                                        <i class="fas fa-store mr-2"></i>Become a Vendor
                                    </a>
                                    @endif
                                @endif
                                <hr class="my-2">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2 border border-primary-600 text-primary-600 rounded-lg hover:bg-primary-600 hover:text-white transition">
                            Login
                        </a>
                        @if(setting('system.allow_registration', true))
                        <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                            Register
                        </a>
                        @endif
                    @endauth

                    <button @click="mobileMenu = !mobileMenu" class="lg:hidden p-2 text-gray-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Search -->
            <div x-show="searchOpen" x-cloak class="md:hidden pb-4">
                <form action="{{ route('shop.index') }}" method="GET">
                    <input type="text" name="search" placeholder="Search products..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                </form>
            </div>
        </div>
        @endif

        <!-- Mobile Menu Overlay -->
        <div x-show="mobileMenu" x-cloak 
             @click="mobileMenu = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 md:hidden">
        </div>
        
        <!-- Mobile Menu Slide-out Panel -->
        <div x-show="mobileMenu" x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed top-0 left-0 h-full w-[300px] max-w-[85vw] bg-white shadow-2xl z-50 md:hidden flex flex-col">
            
            {{-- Menu Header with Close Button --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <a href="{{ route('home') }}" @click="mobileMenu = false" class="flex items-center gap-2">
                    @if(!empty($appSettings['store_logo']))
                        <img src="{{ Storage::url($appSettings['store_logo']) }}" alt="{{ $appSettings['store_name'] ?? 'Store' }}" class="h-9 w-auto">
                    @else
                        <div class="w-9 h-9 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center shadow-md">
                            <i class="fas fa-shopping-bag text-white text-sm"></i>
                        </div>
                        <span class="text-lg font-bold text-gray-900">{{ $appSettings['store_name'] ?? 'Shop' }}</span>
                    @endif
                </a>
                <button @click="mobileMenu = false" class="w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-700 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            {{-- User Section --}}
            <div class="px-5 py-4 bg-gradient-to-r from-primary-50 to-primary-100/50 border-b border-primary-100">
                @auth
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-700 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-user text-white text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                            <a href="{{ route('account.dashboard') }}" @click="mobileMenu = false" class="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1">
                                {{ __('messages.my_account') }}
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="flex gap-3">
                        <a href="{{ route('login') }}" @click="mobileMenu = false" class="flex-1 py-2.5 px-4 bg-primary-600 text-white text-center rounded-xl font-semibold hover:bg-primary-700 transition shadow-md shadow-primary-200">
                            <i class="fas fa-sign-in-alt mr-2"></i>{{ __('messages.login') }}
                        </a>
                        @if(setting('system.allow_registration', true))
                        <a href="{{ route('register') }}" @click="mobileMenu = false" class="flex-1 py-2.5 px-4 border-2 border-primary-600 text-primary-600 text-center rounded-xl font-semibold hover:bg-primary-50 transition">
                            {{ __('messages.register') }}
                        </a>
                        @endif
                    </div>
                @endauth
            </div>
            
            {{-- Scrollable Content Area --}}
            <div class="flex-1 overflow-y-auto overscroll-contain">
                {{-- Main Navigation Links --}}
                <nav class="px-3 py-4">
                    <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Menu</p>
                    @php
                        $navIcons = [
                            'home' => 'fa-home',
                            'shop.index' => 'fa-store',
                            'about' => 'fa-info-circle',
                            'contact' => 'fa-envelope',
                            'blog.index' => 'fa-newspaper',
                        ];
                    @endphp
                    @foreach($headerNav as $item)
                        @php
                            $navLabel = $item['label'];
                            $navTranslations = [
                                'home' => __('messages.home'),
                                'shop.index' => __('messages.shop'),
                                'about' => __('messages.about'),
                                'contact' => __('messages.contact'),
                                'blog.index' => __('messages.blog'),
                            ];
                            if (isset($item['route']) && isset($navTranslations[$item['route']])) {
                                $navLabel = $navTranslations[$item['route']];
                            }
                            $isActive = isset($item['route']) && request()->routeIs($item['route'] . '*');
                            $navIcon = isset($item['route']) && isset($navIcons[$item['route']]) ? $navIcons[$item['route']] : 'fa-link';
                        @endphp
                        <a href="{{ $item['url'] }}" @click="mobileMenu = false"
                           class="flex items-center gap-3 py-3 px-3 rounded-xl transition-all duration-200 {{ $isActive ? 'bg-primary-50 text-primary-600' : 'text-gray-700 hover:bg-gray-50' }}">
                            <span class="w-9 h-9 flex items-center justify-center rounded-lg {{ $isActive ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-500' }}">
                                <i class="fas {{ $navIcon }}"></i>
                            </span>
                            <span class="font-medium flex-1">{{ $navLabel }}</span>
                            <i class="fas fa-chevron-right text-xs {{ $isActive ? 'text-primary-400' : 'text-gray-300' }}"></i>
                        </a>
                    @endforeach
                </nav>
                
                {{-- Categories Section --}}
                @php
                    $mobileCategories = \App\Models\Category::where('is_active', true)->orderBy('name')->take(6)->get();
                @endphp
                @if($mobileCategories->count() > 0)
                <div class="px-3 py-4 border-t border-gray-100">
                    <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('messages.categories') ?? 'Categories' }}</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($mobileCategories as $cat)
                            <a href="{{ route('shop.category', $cat->slug) }}" @click="mobileMenu = false"
                               class="flex items-center gap-2 p-3 bg-gray-50 hover:bg-primary-50 rounded-xl transition-colors group">
                                @if($cat->image)
                                    <img src="{{ asset('storage/' . $cat->image) }}" class="w-8 h-8 rounded-lg object-cover shadow-sm">
                                @else
                                    <span class="w-8 h-8 bg-gradient-to-br from-gray-200 to-gray-300 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-tag text-gray-500 text-xs"></i>
                                    </span>
                                @endif
                                <span class="text-sm font-medium text-gray-700 group-hover:text-primary-600 truncate">{{ $cat->translated_name ?? $cat->name }}</span>
                            </a>
                        @endforeach
                    </div>
                    <a href="{{ route('shop.index') }}" @click="mobileMenu = false" class="flex items-center justify-center gap-2 mt-3 py-2.5 text-primary-600 font-semibold hover:bg-primary-50 rounded-xl transition">
                        <i class="fas fa-th-large"></i>
                        <span>{{ __('messages.view_all_categories') ?? 'View All Categories' }}</span>
                    </a>
                </div>
                @endif
                
                {{-- Account Links (for logged in users) --}}
                @auth
                <div class="px-3 py-4 border-t border-gray-100">
                    <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('messages.my_account') }}</p>
                    
                    <a href="{{ route('account.orders') }}" @click="mobileMenu = false" class="flex items-center gap-3 py-2.5 px-3 text-gray-700 hover:bg-gray-50 rounded-xl transition">
                        <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-sm"></i>
                        </span>
                        <span class="font-medium">{{ __('messages.my_orders') ?? 'My Orders' }}</span>
                    </a>
                    
                    @module('wishlist')
                    <a href="{{ route('account.wishlist') }}" @click="mobileMenu = false" class="flex items-center gap-3 py-2.5 px-3 text-gray-700 hover:bg-gray-50 rounded-xl transition">
                        <span class="w-8 h-8 bg-pink-100 text-pink-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-heart text-sm"></i>
                        </span>
                        <span class="font-medium">{{ __('messages.wishlist') ?? 'Wishlist' }}</span>
                    </a>
                    @endmodule
                    
                    <a href="{{ route('account.profile') }}" @click="mobileMenu = false" class="flex items-center gap-3 py-2.5 px-3 text-gray-700 hover:bg-gray-50 rounded-xl transition">
                        <span class="w-8 h-8 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-cog text-sm"></i>
                        </span>
                        <span class="font-medium">{{ __('messages.profile') ?? 'Profile' }}</span>
                    </a>
                    
                    @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.dashboard') }}" @click="mobileMenu = false" class="flex items-center gap-3 py-2.5 px-3 text-gray-700 hover:bg-gray-50 rounded-xl transition">
                        <span class="w-8 h-8 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-cog text-sm"></i>
                        </span>
                        <span class="font-medium">Admin Panel</span>
                    </a>
                    @endif
                    
                    @if(auth()->user()->is_driver && setting('features.driver_portal', true))
                    <a href="{{ route('driver.dashboard') }}" @click="mobileMenu = false" class="flex items-center gap-3 py-2.5 px-3 text-emerald-700 hover:bg-emerald-50 rounded-xl transition">
                        <span class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-truck text-sm"></i>
                        </span>
                        <span class="font-medium">Driver Portal</span>
                    </a>
                    @endif
                    
                    @if(setting('features.multi_vendor'))
                        @if(auth()->user()->vendor)
                        <a href="{{ route('vendor.dashboard') }}" @click="mobileMenu = false" class="flex items-center gap-3 py-2.5 px-3 text-gray-700 hover:bg-gray-50 rounded-xl transition">
                            <span class="w-8 h-8 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-store text-sm"></i>
                            </span>
                            <span class="font-medium">Vendor Panel</span>
                        </a>
                        @else
                        <a href="{{ route('vendor.register') }}" @click="mobileMenu = false" class="flex items-center gap-3 py-2.5 px-3 text-emerald-700 hover:bg-emerald-50 rounded-xl transition">
                            <span class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-store text-sm"></i>
                            </span>
                            <span class="font-medium">Become a Vendor</span>
                        </a>
                        @endif
                    @endif
                </div>
                @endauth
                
                {{-- Language Switcher Section --}}
                @php
                    $mobileMenuLanguages = $appSettings['available_languages'] ?? ['en' => 'English'];
                    $mobileMenuCurrentLocale = $appSettings['current_locale'] ?? 'en';
                    $mobileMenuLocalizationEnabled = $appSettings['features']['localization_enabled'] ?? true;
                @endphp
                @if($mobileMenuLocalizationEnabled && count($mobileMenuLanguages) > 1)
                <div class="px-3 py-4 border-t border-gray-100">
                    <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        <i class="fas fa-globe mr-1"></i> {{ __('messages.language') ?? 'Language' }}
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($mobileMenuLanguages as $code => $name)
                            <a href="{{ route('locale.switch', $code) }}" @click="mobileMenu = false"
                               class="flex items-center justify-center gap-2 p-3 rounded-xl transition-colors {{ $mobileMenuCurrentLocale === $code ? 'bg-primary-100 text-primary-700 font-semibold border-2 border-primary-300' : 'bg-gray-50 text-gray-700 hover:bg-primary-50 hover:text-primary-600' }}">
                                @if($mobileMenuCurrentLocale === $code)
                                    <i class="fas fa-check-circle text-primary-500"></i>
                                @endif
                                <span class="text-sm">{{ $name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            
            {{-- Footer Section (Logout for logged in, or contact info) --}}
            <div class="border-t border-gray-100 bg-gray-50">
                @auth
                <div class="p-4">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center justify-center gap-2 w-full py-3 bg-red-50 text-red-600 font-semibold rounded-xl hover:bg-red-100 transition">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>{{ __('messages.logout') ?? 'Logout' }}</span>
                        </button>
                    </form>
                </div>
                @else
                <div class="p-4 text-center">
                    @if(!empty($appSettings['store_phone']))
                    <a href="tel:{{ $appSettings['store_phone'] }}" class="flex items-center justify-center gap-2 text-gray-600 hover:text-primary-600 py-2">
                        <i class="fas fa-phone-alt"></i>
                        <span class="text-sm font-medium">{{ $appSettings['store_phone'] }}</span>
                    </a>
                    @endif
                    <div class="flex items-center justify-center gap-4 mt-2">
                        @if(!empty($appSettings['social']['facebook']))
                        <a href="{{ $appSettings['social']['facebook'] }}" target="_blank" class="w-9 h-9 bg-gray-200 hover:bg-primary-100 hover:text-primary-600 rounded-full flex items-center justify-center text-gray-500 transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        @endif
                        @if(!empty($appSettings['social']['instagram']))
                        <a href="{{ $appSettings['social']['instagram'] }}" target="_blank" class="w-9 h-9 bg-gray-200 hover:bg-primary-100 hover:text-primary-600 rounded-full flex items-center justify-center text-gray-500 transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        @endif
                        @if(!empty($appSettings['social']['twitter']))
                        <a href="{{ $appSettings['social']['twitter'] }}" target="_blank" class="w-9 h-9 bg-gray-200 hover:bg-primary-100 hover:text-primary-600 rounded-full flex items-center justify-center text-gray-500 transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        @endif
                        @if(!empty($appSettings['social']['whatsapp']))
                        <a href="https://wa.me/{{ $appSettings['social']['whatsapp'] }}" target="_blank" class="w-9 h-9 bg-gray-200 hover:bg-green-100 hover:text-green-600 rounded-full flex items-center justify-center text-gray-500 transition">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        @endif
                    </div>
                </div>
                @endauth
            </div>
        </div>
    </header>
