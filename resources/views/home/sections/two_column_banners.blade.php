@if($appSettings['homepage']['show_two_column_banners'] ?? true)
@php
    $banner1Badge = $appSettings['homepage']['banner1_badge'] ?? 'GET 50% DISCOUNT';
    $banner1Title = $appSettings['homepage']['banner1_title'] ?? 'Fresh Grocery Items Now Available';
    $banner1Button = $appSettings['homepage']['banner1_button'] ?? 'Shop Now';
    $banner1Link = $appSettings['homepage']['banner1_link'] ?? '/shop?category=grocery';
    $banner1BgType = $appSettings['homepage']['banner1_bg_type'] ?? 'image';
    $banner1BgColor = $appSettings['homepage']['banner1_bg_color'] ?? '';
    $banner1Image = !empty($appSettings['homepage']['banner1_image']) ? asset('storage/' . $appSettings['homepage']['banner1_image']) : '';
    $banner1FallbackImage = 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=600&h=400&fit=crop';
    
    $banner2Badge = $appSettings['homepage']['banner2_badge'] ?? 'GET 50% DISCOUNT';
    $banner2Title = $appSettings['homepage']['banner2_title'] ?? 'New Home Appliance For Your Home';
    $banner2Button = $appSettings['homepage']['banner2_button'] ?? 'Shop Now';
    $banner2Link = $appSettings['homepage']['banner2_link'] ?? '/shop?category=appliances';
    $banner2BgType = $appSettings['homepage']['banner2_bg_type'] ?? 'image';
    $banner2BgColor = $appSettings['homepage']['banner2_bg_color'] ?? '';
    $banner2Image = !empty($appSettings['homepage']['banner2_image']) ? asset('storage/' . $appSettings['homepage']['banner2_image']) : '';
    $banner2FallbackImage = 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600&h=400&fit=crop';
@endphp
<section class="py-10 md:py-16 homepage-body">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-5 md:gap-8">
            <!-- Banner 1 -->
            <div class="relative rounded-3xl overflow-hidden h-56 sm:h-72 md:h-80 lg:h-96 group cursor-pointer shadow-lg hover:shadow-2xl transition-all duration-500">
                @if($banner1BgType === 'color')
                    <div class="absolute inset-0" style="background-color: {{ $banner1BgColor ?: '#111827' }};"></div>
                @else
                    <img src="{{ $banner1Image ?: $banner1FallbackImage }}" alt="{{ $banner1Title }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out">
                @endif
                <div class="absolute inset-0 bg-gradient-to-r from-black/45 via-black/20 to-transparent"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/15 to-transparent"></div>
                
                <!-- Content -->
                <div class="absolute inset-0 p-5 sm:p-6 md:p-8 lg:p-10 flex flex-col justify-end md:justify-center">
                    <span class="inline-flex items-center w-fit px-3 py-1.5 bg-white/20 backdrop-blur-sm text-white text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-full mb-3 border border-white/20">
                        <i class="fas fa-tag mr-1.5"></i>{{ $banner1Badge }}
                    </span>
                    <h3 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-3 md:mb-4 max-w-sm leading-tight drop-shadow-lg">{{ $banner1Title }}</h3>
                    <a href="{{ $banner1Link }}" class="inline-flex items-center px-5 sm:px-6 py-2.5 sm:py-3 bg-white text-gray-900 font-semibold rounded-xl hover:bg-primary-600 hover:text-white transition-all duration-300 text-sm w-fit shadow-lg hover:shadow-xl group/btn hover:-translate-y-0.5">
                        {{ $banner1Button }}
                        <i class="fas fa-arrow-right ml-2 group-hover/btn:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                
                <!-- Decorative corner accent -->
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-primary-500/30 to-transparent"></div>
            </div>
            
            <!-- Banner 2 -->
            <div class="relative rounded-3xl overflow-hidden h-56 sm:h-72 md:h-80 lg:h-96 group cursor-pointer shadow-lg hover:shadow-2xl transition-all duration-500">
                @if($banner2BgType === 'color')
                    <div class="absolute inset-0" style="background-color: {{ $banner2BgColor ?: '#111827' }};"></div>
                @else
                    <img src="{{ $banner2Image ?: $banner2FallbackImage }}" alt="{{ $banner2Title }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out">
                @endif
                <div class="absolute inset-0 bg-gradient-to-r from-black/45 via-black/20 to-transparent"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/15 to-transparent"></div>
                
                <!-- Content -->
                <div class="absolute inset-0 p-5 sm:p-6 md:p-8 lg:p-10 flex flex-col justify-end md:justify-center">
                    <span class="inline-flex items-center w-fit px-3 py-1.5 bg-white/20 backdrop-blur-sm text-white text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-full mb-3 border border-white/20">
                        <i class="fas fa-percent mr-1.5"></i>{{ $banner2Badge }}
                    </span>
                    <h3 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-3 md:mb-4 max-w-sm leading-tight drop-shadow-lg">{{ $banner2Title }}</h3>
                    <a href="{{ $banner2Link }}" class="inline-flex items-center px-5 sm:px-6 py-2.5 sm:py-3 bg-white text-gray-900 font-semibold rounded-xl hover:bg-primary-600 hover:text-white transition-all duration-300 text-sm w-fit shadow-lg hover:shadow-xl group/btn hover:-translate-y-0.5">
                        {{ $banner2Button }}
                        <i class="fas fa-arrow-right ml-2 group-hover/btn:translate-x-1 transition-transform"></i>
                    </a>
                </div>
                
                <!-- Decorative corner accent -->
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-primary-500/30 to-transparent"></div>
            </div>
        </div>
    </div>
</section>
@endif
