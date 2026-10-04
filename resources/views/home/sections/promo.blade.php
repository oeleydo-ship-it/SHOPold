@if($appSettings['homepage']['show_promo'] ?? true)
    @php
        $promoBadge = $appSettings['homepage']['promo_badge'] ?? 'Limited Time';
        $promoTitle = $appSettings['homepage']['promo_title'] ?? 'Up to 50% Off on Selected Items';
        $promoDesc = $appSettings['homepage']['promo_desc'] ?? "Don't miss out on our biggest sale of the season. Shop now and save big!";
        $promoButton = $appSettings['homepage']['promo_button'] ?? 'Shop Sale';
        $promoLink = $appSettings['homepage']['promo_link'] ?? '/shop?sale=1';
        $promoDiscount = $appSettings['homepage']['promo_discount'] ?? '50';
        $promoBgType = $appSettings['homepage']['promo_bg_type'] ?? 'color';
        $promoBgImage = $appSettings['homepage']['promo_bg_image'] ?? '';
    @endphp
    <section class="py-10 md:py-16 homepage-body relative overflow-hidden"
        style="{{ $promoBgType === 'color' ? ($promoBgColor ? 'background-color: ' . $promoBgColor . ';' : 'background-color: #111827;') : '' }}">
        
        @if($promoBgType === 'image' && $promoBgImage)
            <!-- Background Image -->
            <div class="absolute inset-0 z-0">
                <img src="{{ asset('storage/' . $promoBgImage) }}" alt="Promo Background" 
                     class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/50"></div>
            </div>
        @endif

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid md:grid-cols-2 gap-8 md:gap-12 items-center">
                <div class="text-white text-center md:text-left">
                    <span
                        class="inline-flex items-center px-4 py-1.5 bg-primary-600 rounded-full text-xs sm:text-sm font-semibold mb-4 md:mb-6">
                        <i class="fas fa-clock mr-2"></i>{{ $promoBadge }}
                    </span>
                    <h2
                        class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold mb-4 md:mb-6 homepage-heading leading-tight">
                        {{ $promoTitle }}
                    </h2>
                    <p
                        class="text-gray-300 mb-6 md:mb-8 text-sm sm:text-base md:text-lg leading-relaxed max-w-lg mx-auto md:mx-0">
                        {{ $promoDesc }}
                    </p>
                    <a href="{{ $promoLink }}"
                        class="inline-flex items-center px-6 sm:px-8 py-3 sm:py-4 bg-white text-gray-900 font-semibold rounded-lg hover:bg-primary-50 transition-colors duration-200 group">
                        {{ $promoButton }}
                        <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>

                <!-- Discount Display -->
                <div class="flex justify-center md:justify-end">
                    <div class="relative">
                        <!-- Main discount circle -->
                        <div
                            class="relative w-40 h-40 sm:w-48 sm:h-48 md:w-56 md:h-56 lg:w-64 lg:h-64 rounded-full bg-primary-600 flex flex-col items-center justify-center border-4 border-white/20">
                            <div class="text-5xl sm:text-6xl md:text-7xl lg:text-8xl font-black text-white leading-none">
                                {{ $promoDiscount }}
                            </div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl sm:text-3xl md:text-4xl font-bold text-white/90">%</span>
                                <span
                                    class="text-lg sm:text-xl md:text-2xl font-semibold text-white/80 uppercase tracking-wider">OFF</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif