{{-- Product page: Frequently bought together. Shared by every theme; a theme can replace it at themes/<slug>/shop/product/cross-sell.blade.php --}}
    <!-- Cross-Sell Products -->
    @if($crossSellEnabled && $crossSellProducts->count() > 0)
    <div class="mt-12">
        <h3 class="text-2xl font-bold mb-6">{{ $crossSellTitle }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($crossSellProducts as $crossSellProduct)
                <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden transform hover:-translate-y-1">
                    <div class="relative aspect-square overflow-hidden bg-gray-50">
                        @if($crossSellProduct->image)
                            <img src="{{ $crossSellProduct->image_url }}" alt="{{ $crossSellProduct->translated_name ?? $crossSellProduct->name }}" 
                                 class="w-full h-full object-contain p-2 group-hover:scale-105 transition duration-500"
                                 loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
                                <i class="fas fa-image text-5xl text-gray-300"></i>
                            </div>
                        @endif
                        
                        @if($crossSellProduct->sale_price)
                            <span class="absolute top-3 left-3 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">
                                -{{ $crossSellProduct->discount_percent }}%
                            </span>
                        @endif
                    </div>
                    
                    <div class="p-4">
                        <a href="{{ route('shop.category', $crossSellProduct->category->slug) }}" class="text-xs text-primary-600 font-medium uppercase tracking-wide">
                            {{ $crossSellProduct->category->translated_name ?? $crossSellProduct->category->name }}
                        </a>
                        <h4 class="mt-1">
                            <a href="{{ route('shop.show', $crossSellProduct->slug) }}" class="font-semibold text-gray-900 hover:text-primary-600 transition line-clamp-2">
                                {{ $crossSellProduct->translated_name ?? $crossSellProduct->name }}
                            </a>
                        </h4>
                        
                        <div class="mt-3">
                            @if($crossSellProduct->sale_price)
                                <span class="text-lg font-bold text-primary-600">{{ format_currency($crossSellProduct->sale_price) }}</span>
                                <span class="text-sm text-gray-400 line-through ml-1">{{ format_currency($crossSellProduct->price) }}</span>
                            @else
                                <span class="text-lg font-bold text-gray-900">{{ format_currency($crossSellProduct->price) }}</span>
                            @endif
                        </div>
                        
                        <div class="mt-3">
                            @if($crossSellProduct->product_type === 'variable')
                                {{-- Variable products link to product page --}}
                                <a href="{{ route('shop.show', $crossSellProduct->slug) }}" 
                                   class="w-full block text-center py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                                    <i class="fas fa-eye mr-2"></i>View Options
                                </a>
                            @elseif($crossSellProduct->isInStock())
                                {{-- Simple in-stock products show add-to-cart --}}
                                <button type="button" 
                                        class="cross-sell-add-to-cart w-full py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                                        data-product-id="{{ $crossSellProduct->id }}">
                                    <i class="fas fa-shopping-cart mr-2"></i>Add to Cart
                                </button>
                            @else
                                {{-- Out of stock indicator --}}
                                <span class="w-full block text-center py-2 bg-gray-100 text-red-500 font-medium rounded-lg">
                                    <i class="fas fa-times-circle mr-2"></i>Out of Stock
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
