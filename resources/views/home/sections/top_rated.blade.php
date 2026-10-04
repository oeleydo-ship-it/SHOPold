@if($appSettings['homepage']['show_top_rated'] ?? true)
    @if(isset($topRatedProducts) && $topRatedProducts->count() > 0)
        @php
            $topRatedTitle = $appSettings['homepage']['top_rated_title'] ?? __('messages.top_rated');
            $topRatedPromoBadge = $appSettings['homepage']['top_rated_promo_badge'] ?? 'GET 50% DISCOUNT';
            $topRatedPromoTitle = $appSettings['homepage']['top_rated_promo_title'] ?? 'Get Up to 20% off for Computer Gadget';
            $topRatedPromoDesc = $appSettings['homepage']['top_rated_promo_desc'] ?? 'We collect latest iphone mobile series and apple gadget collection.';
            $topRatedPromoButton = $appSettings['homepage']['top_rated_promo_button'] ?? 'Explore More';
            $topRatedPromoLink = $appSettings['homepage']['top_rated_promo_link'] ?? '/shop?category=electronics';
            $topRatedPromoImage = !empty($appSettings['homepage']['top_rated_promo_image']) ? asset('storage/' . $appSettings['homepage']['top_rated_promo_image']) : 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=300&fit=crop';
        @endphp
        <section class="py-12 md:py-20 homepage-body bg-gradient-to-b from-white to-primary-50/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Section Header -->
                <div class="flex items-center justify-between gap-4 mb-6 md:mb-10">
                    <div class="min-w-0 flex-1">
                        <span
                            class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-full mb-2">
                            <i class="fas fa-star mr-1.5"></i>{{ __('messages.customer_favorites') }}
                        </span>
                        <h2
                            class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 homepage-heading truncate">
                            {{ $topRatedTitle }}</h2>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button onclick="scrollTopRated(-1)"
                            class="w-9 h-9 sm:w-10 sm:h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:border-primary-500 hover:bg-primary-50 hover:text-primary-600 transition-all duration-300 shadow-sm">
                            <i class="fas fa-chevron-left text-gray-500 text-sm"></i>
                        </button>
                        <button onclick="scrollTopRated(1)"
                            class="w-9 h-9 sm:w-10 sm:h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:border-primary-500 hover:bg-primary-50 hover:text-primary-600 transition-all duration-300 shadow-sm">
                            <i class="fas fa-chevron-right text-gray-500 text-sm"></i>
                        </button>
                    </div>
                </div>

                <div class="grid lg:grid-cols-3 gap-6 lg:gap-8">
                    <!-- Products Grid -->
                    <div class="lg:col-span-2 order-2 lg:order-1">
                        <div id="topRatedContainer" class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-5">
                            @foreach($topRatedProducts as $product)
                                <div
                                    class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden border border-gray-100 hover:border-primary-200">
                                    <div class="flex">
                                        <!-- Product Image -->
                                        <div
                                            class="w-28 h-28 sm:w-32 sm:h-32 md:w-36 md:h-36 flex-shrink-0 bg-white p-3 relative overflow-hidden">
                                            @if($product->image)
                                                <img src="{{ $product->image_url }}" alt="{{ $product->translated_name }}"
                                                    class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <i class="fas fa-image text-3xl text-gray-300"></i>
                                                </div>
                                            @endif
                                            @if($product->sale_price)
                                                <span
                                                    class="absolute top-2 left-2 bg-gradient-to-r from-red-500 to-pink-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
                                                    -{{ $product->discount_percent }}%
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Product Info -->
                                        <div class="p-4 flex flex-col justify-between flex-1 min-w-0">
                                            <div>
                                                <a href="{{ route('shop.category', $product->category->slug) }}"
                                                    class="text-xs text-primary-600 font-medium hover:text-primary-700 transition">
                                                    {{ $product->category->translated_name ?? $product->category->name }}
                                                </a>
                                                <h3 class="mt-1.5">
                                                    <a href="{{ route('shop.show', $product->slug) }}"
                                                        class="font-semibold text-gray-900 hover:text-primary-600 transition line-clamp-2 text-sm md:text-base leading-tight">
                                                        {{ $product->translated_name }}
                                                    </a>
                                                </h3>

                                                <!-- Rating -->
                                                <div class="flex items-center mt-2 gap-1.5">
                                                    <div class="flex text-yellow-400 text-xs gap-0.5">
                                                        @for($i = 1; $i <= 5; $i++)
                                                            <i
                                                                class="fas fa-star{{ $i <= round($product->avg_rating ?? $product->average_rating) ? '' : ' text-gray-200' }}"></i>
                                                        @endfor
                                                    </div>
                                                    <span
                                                        class="text-xs text-gray-500 font-medium">({{ $product->reviews_count ?? $product->review_count }})</span>
                                                </div>
                                            </div>

                                            <!-- Price & Actions -->
                                            <div class="mt-3 flex items-center justify-between">
                                                <div class="flex items-baseline gap-2">
                                                    @if($product->sale_price)
                                                        <span
                                                            class="text-lg md:text-xl font-bold text-primary-600">{{ format_currency($product->sale_price) }}</span>
                                                        <span
                                                            class="text-xs text-gray-400 line-through">{{ format_currency($product->price) }}</span>
                                                    @else
                                                        <span
                                                            class="text-lg md:text-xl font-bold text-gray-900">{{ format_currency($product->price) }}</span>
                                                    @endif
                                                </div>

                                                <div
                                                    class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                                    <form action="{{ route('cart.add') }}" method="POST" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                        <button type="submit"
                                                            class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center text-primary-600 hover:bg-primary-600 hover:text-white transition-all duration-300 text-xs"
                                                            title="Add to Cart">
                                                            <i class="fas fa-shopping-cart"></i>
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('shop.show', $product->slug) }}"
                                                        class="w-8 h-8 bg-white border border-gray-200 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-800 hover:text-white hover:border-gray-800 transition-all duration-300 text-xs"
                                                        title="Quick View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @auth
                                                        @module('wishlist')
                                                        <form action="{{ route('account.wishlist.add') }}" method="POST" class="inline">
                                                            @csrf
                                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                            <button type="submit"
                                                                class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center text-red-400 hover:bg-red-500 hover:text-white transition-all duration-300 text-xs"
                                                                title="Add to Wishlist">
                                                                <i class="fas fa-heart"></i>
                                                            </button>
                                                        </form>
                                                        @endmodule
                                                    @endauth
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Promotional Banner -->
                    <div class="lg:col-span-1 order-1 lg:order-2">
                        <div
                            class="bg-gradient-to-br from-primary-50 via-blue-50 to-indigo-100 rounded-3xl overflow-hidden h-full relative group shadow-lg hover:shadow-xl transition-shadow duration-500">
                            <!-- Decorative Elements -->
                            <div
                                class="absolute top-0 right-0 w-32 h-32 bg-primary-200/30 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl">
                            </div>
                            <div
                                class="absolute bottom-0 left-0 w-24 h-24 bg-blue-200/40 rounded-full translate-y-1/2 -translate-x-1/2 blur-xl">
                            </div>

                            <div class="p-6 md:p-8 relative z-10">
                                <span
                                    class="inline-flex items-center px-3 py-1.5 bg-primary-600 text-white text-xs font-bold uppercase tracking-wider rounded-full shadow-lg">
                                    <i class="fas fa-bolt mr-1.5"></i>{{ $topRatedPromoBadge }}
                                </span>
                                <h3 class="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900 mt-4 mb-3 leading-tight">
                                    {{ $topRatedPromoTitle }}</h3>
                                <p class="text-gray-600 text-sm md:text-base mb-6 leading-relaxed">{{ $topRatedPromoDesc }}</p>
                                <a href="{{ $topRatedPromoLink }}"
                                    class="inline-flex items-center px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-all duration-300 shadow-lg hover:shadow-xl hover:-translate-y-0.5 text-sm md:text-base group">
                                    {{ $topRatedPromoButton }}
                                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            </div>

                            <!-- Promo Image -->
                            <div class="relative h-52 md:h-64 lg:h-72 mt-4 overflow-hidden rounded-2xl">
                                <img src="{{ $topRatedPromoImage }}" alt="Promo"
                                    class="absolute inset-0 w-full h-full object-cover drop-shadow-2xl group-hover:scale-105 transition-transform duration-700">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <script>
            function scrollTopRated(direction) {
                const container = document.getElementById('topRatedContainer');
                const cardWidth = container.querySelector('div')?.offsetWidth || 300;
                container.scrollBy({ left: direction * (cardWidth + 20), behavior: 'smooth' });
            }
        </script>
    @else
        {{-- Show placeholder when no top rated products available --}}
        <section class="py-12 md:py-20 homepage-body bg-gradient-to-b from-white to-primary-50/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 homepage-heading mb-6">
                    {{ $appSettings['homepage']['top_rated_title'] ?? 'Top Rated' }}</h2>
                <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-star text-3xl text-yellow-400"></i>
                    </div>
                    <p class="text-gray-500 text-lg">No rated products yet. Be the first to review!</p>
                </div>
            </div>
        </section>
    @endif
@endif