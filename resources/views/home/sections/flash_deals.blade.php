@if($appSettings['homepage']['show_flash_deals'] ?? true)
    @if(isset($flashDeals) && $flashDeals->count() > 0)
        @php
            $flashDealsTitle = $appSettings['homepage']['flash_deals_title'] ?? __('messages.flash_deals');
            $flashDealsButton = $appSettings['homepage']['flash_deals_button'] ?? __('messages.shop_now');
            $flashDealsEndDate = $appSettings['homepage']['flash_deals_end_date'] ?? now()->addDays(7)->format('Y-m-d H:i:s');
        @endphp
        <section class="py-12 md:py-20 homepage-body bg-gradient-to-b from-white to-primary-50/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-4 gap-6 lg:gap-8">
                    <!-- Featured Flash Deal (Large Card) -->
                    @if($flashDeals->first())
                        @php $mainDeal = $flashDeals->first(); @endphp
                        <div class="lg:col-span-1 order-2 lg:order-1">
                            <div
                                class="bg-gradient-to-br from-primary-600 via-primary-700 to-primary-800 rounded-3xl shadow-2xl overflow-hidden relative group h-full">
                                <!-- Decorative elements -->
                                <div
                                    class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl">
                                </div>
                                <div
                                    class="absolute bottom-0 left-0 w-24 h-24 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/2 blur-xl">
                                </div>

                                <div class="p-5 md:p-6 relative z-10">
                                    <span
                                        class="inline-flex items-center px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-xs font-bold rounded-full mb-3">
                                        <i class="fas fa-fire mr-1.5 text-yellow-400"></i>HOT DEAL
                                    </span>
                                    <h3 class="font-bold text-white text-lg md:text-xl mb-2 line-clamp-2">{{ $mainDeal->name }}</h3>
                                    <div class="flex items-center mb-3 gap-1.5">
                                        <div class="flex text-yellow-400 text-sm gap-0.5">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i
                                                    class="fas fa-star{{ $i <= round($mainDeal->average_rating) ? '' : ' text-white/30' }}"></i>
                                            @endfor
                                        </div>
                                        <span class="text-xs text-white/70">({{ $mainDeal->review_count }})</span>
                                    </div>
                                    <div class="flex items-baseline gap-2 mb-2">
                                        <span
                                            class="text-2xl md:text-3xl font-black text-white">{{ format_currency($mainDeal->sale_price) }}</span>
                                        <span
                                            class="text-sm text-white/60 line-through">{{ format_currency($mainDeal->price) }}</span>
                                    </div>
                                </div>

                                <div class="relative aspect-square bg-white/10 backdrop-blur-sm mx-4 rounded-2xl overflow-hidden">
                                    @if($mainDeal->image)
                                        <img src="{{ $mainDeal->image_url }}" alt="{{ $mainDeal->name }}"
                                            class="w-full h-full object-contain p-4 group-hover:scale-110 transition-transform duration-700">
                                    @endif
                                    <span
                                        class="absolute top-3 right-3 bg-gradient-to-r from-red-500 to-pink-500 text-white text-sm font-bold px-3 py-1.5 rounded-full shadow-lg">
                                        -{{ $mainDeal->discount_percent }}%
                                    </span>
                                </div>

                                <!-- Countdown Timer -->
                                <div class="p-4 md:p-5 mt-2" x-data="countdown('{{ $flashDealsEndDate }}')" x-init="init()">
                                    <p class="text-white/70 text-xs text-center mb-3 font-medium uppercase tracking-wider">Ends In
                                    </p>
                                    <div class="grid grid-cols-4 gap-2 text-center">
                                        <div>
                                            <div class="bg-white/20 backdrop-blur-sm rounded-xl py-2.5 shadow-inner">
                                                <span class="text-xl md:text-2xl font-black text-white" x-text="days">00</span>
                                            </div>
                                            <span class="text-[10px] text-white/60 mt-1 block">Days</span>
                                        </div>
                                        <div>
                                            <div class="bg-white/20 backdrop-blur-sm rounded-xl py-2.5 shadow-inner">
                                                <span class="text-xl md:text-2xl font-black text-white" x-text="hours">00</span>
                                            </div>
                                            <span class="text-[10px] text-white/60 mt-1 block">Hours</span>
                                        </div>
                                        <div>
                                            <div class="bg-white/20 backdrop-blur-sm rounded-xl py-2.5 shadow-inner">
                                                <span class="text-xl md:text-2xl font-black text-white" x-text="minutes">00</span>
                                            </div>
                                            <span class="text-[10px] text-white/60 mt-1 block">Mins</span>
                                        </div>
                                        <div>
                                            <div class="bg-white/20 backdrop-blur-sm rounded-xl py-2.5 shadow-inner">
                                                <span class="text-xl md:text-2xl font-black text-white" x-text="seconds">00</span>
                                            </div>
                                            <span class="text-[10px] text-white/60 mt-1 block">Secs</span>
                                        </div>
                                    </div>

                                    <a href="{{ route('shop.show', $mainDeal->slug) }}"
                                        class="mt-4 w-full py-3 bg-white text-primary-700 font-bold rounded-xl hover:bg-primary-50 transition-all duration-300 flex items-center justify-center gap-2 group/btn shadow-lg">
                                        Shop Now
                                        <i class="fas fa-arrow-right group-hover/btn:translate-x-1 transition-transform"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Flash Deals Grid -->
                    <div class="lg:col-span-3 order-1 lg:order-2">
                        <!-- Section Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 md:mb-8">
                            <div>
                                <span
                                    class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold uppercase tracking-wider rounded-full mb-2">
                                    <i class="fas fa-bolt mr-1.5"></i>Limited Time
                                </span>
                                <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 homepage-heading">
                                    {{ $flashDealsTitle }}</h2>
                            </div>
                            <a href="{{ route('shop.index') }}?sale=1"
                                class="inline-flex items-center px-5 py-2.5 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl group">
                                {{ $flashDealsButton }}
                                <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5">
                            @foreach($flashDeals->skip(1) as $product)
                                <div
                                    class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden border border-gray-100 hover:border-primary-200 group">
                                    <div class="relative aspect-square bg-white overflow-hidden">
                                        @if($product->image)
                                            <img src="{{ $product->image_url }}" alt="{{ $product->translated_name }}"
                                                class="w-full h-full object-contain p-4 group-hover:scale-110 transition-transform duration-700">
                                        @endif
                                        <span
                                            class="absolute top-3 right-3 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-lg flex items-center">
                                            <i class="fas fa-bolt mr-1"></i>{{ $product->discount_percent }}%
                                        </span>

                                        <!-- Quick actions on hover -->
                                        <div
                                            class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center gap-2">
                                            <form action="{{ route('cart.add') }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <button type="submit"
                                                    class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-gray-900 hover:bg-primary-600 hover:text-white transition-all duration-300 shadow-lg">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('shop.show', $product->slug) }}"
                                                class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-gray-900 hover:bg-gray-800 hover:text-white transition-all duration-300 shadow-lg">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Countdown bar -->
                                    <div class="px-4 py-2.5 bg-gradient-to-r from-gray-900 to-gray-800"
                                        x-data="countdown('{{ $flashDealsEndDate }}')" x-init="init()">
                                        <div class="flex justify-center items-center gap-3 text-xs">
                                            <span class="text-gray-400 font-medium">Ends in:</span>
                                            <div class="flex gap-1.5">
                                                <span class="bg-red-500 text-white px-2 py-1 rounded-md font-bold"><span
                                                        x-text="days">00</span>d</span>
                                                <span class="bg-red-500 text-white px-2 py-1 rounded-md font-bold"><span
                                                        x-text="hours">00</span>h</span>
                                                <span class="bg-red-500 text-white px-2 py-1 rounded-md font-bold"><span
                                                        x-text="minutes">00</span>m</span>
                                                <span class="bg-red-500 text-white px-2 py-1 rounded-md font-bold"><span
                                                        x-text="seconds">00</span>s</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-4">
                                        <a href="{{ route('shop.category', $product->category->slug) }}"
                                            class="text-xs text-primary-600 font-semibold hover:text-primary-700 transition">{{ $product->category->translated_name ?? $product->category->name }}</a>
                                        <h3 class="mt-1.5">
                                            <a href="{{ route('shop.show', $product->slug) }}"
                                                class="font-semibold text-gray-900 hover:text-primary-600 transition line-clamp-2 text-sm leading-snug">{{ $product->translated_name }}</a>
                                        </h3>
                                        <div class="flex items-center mt-2 gap-1.5">
                                            <div class="flex text-yellow-400 text-xs gap-0.5">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <i
                                                        class="fas fa-star{{ $i <= round($product->average_rating) ? '' : ' text-gray-200' }}"></i>
                                                @endfor
                                            </div>
                                            <span class="text-xs text-gray-500">({{ $product->review_count }})</span>
                                        </div>
                                        <div class="mt-3 flex items-baseline gap-2">
                                            <span
                                                class="text-xl font-bold text-primary-600">{{ format_currency($product->sale_price) }}</span>
                                            <span
                                                class="text-sm text-gray-400 line-through">{{ format_currency($product->price) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <script>
            function countdown(endDate) {
                return {
                    days: '00', hours: '00', minutes: '00', seconds: '00',
                    init() { this.updateCountdown(); setInterval(() => this.updateCountdown(), 1000); },
                    updateCountdown() {
                        const diff = new Date(endDate).getTime() - new Date().getTime();
                        if (diff > 0) {
                            this.days = String(Math.floor(diff / (1000 * 60 * 60 * 24))).padStart(2, '0');
                            this.hours = String(Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))).padStart(2, '0');
                            this.minutes = String(Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
                            this.seconds = String(Math.floor((diff % (1000 * 60)) / 1000)).padStart(2, '0');
                        }
                    }
                }
            }
        </script>
    @else
        {{-- Show placeholder when no flash deals available --}}
        <section class="py-12 md:py-20 homepage-body bg-gradient-to-b from-white to-primary-50/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <span
                            class="inline-flex items-center px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold uppercase tracking-wider rounded-full mb-2">
                            <i class="fas fa-bolt mr-1.5"></i>Limited Time
                        </span>
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 homepage-heading">
                            {{ $appSettings['homepage']['flash_deals_title'] ?? 'Super Flash Sale' }}</h2>
                    </div>
                    <a href="{{ route('shop.index') }}?sale=1"
                        class="inline-flex items-center px-5 py-2.5 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition">{{ $appSettings['homepage']['flash_deals_button'] ?? 'Shop More' }}</a>
                </div>
                <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-bolt text-3xl text-red-400"></i>
                    </div>
                    <p class="text-gray-500 text-lg">No flash deals available at the moment. Check back soon!</p>
                </div>
            </div>
        </section>
    @endif
@endif