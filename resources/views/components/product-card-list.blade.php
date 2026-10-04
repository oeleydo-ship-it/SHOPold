<div class="group bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100 hover:border-primary-200">
    <div class="flex flex-col sm:flex-row">
        <!-- Image -->
        <div class="relative w-full sm:w-48 md:w-56 flex-shrink-0 aspect-square sm:aspect-auto bg-gradient-to-br from-gray-50 to-gray-100">
            <a href="{{ route('shop.show', $product->slug) }}" class="block w-full h-full">
                @if($product->image)
                    <img src="{{ $product->image_url }}" alt="{{ $product->translated_name }}" 
                         class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-500"
                         loading="lazy">
                @else
                    <div class="w-full h-full flex items-center justify-center min-h-[200px]">
                        <i class="fas fa-image text-4xl text-gray-200"></i>
                    </div>
                @endif
            </a>
            
            <!-- Badges -->
            <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                @if($product->sale_price)
                    <span class="bg-gradient-to-r from-rose-500 to-pink-500 text-white text-xs font-bold px-2 py-1 rounded-lg shadow">
                        -{{ $product->discount_percent }}%
                    </span>
                @endif
                @if($product->created_at && $product->created_at->diffInDays(now()) < 14)
                    <span class="bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-xs font-bold px-2 py-1 rounded-lg shadow">
                        {{ __('messages.new') ?? 'NEW' }}
                    </span>
                @endif
            </div>
        </div>
        
        <!-- Content -->
        <div class="flex-1 p-5 flex flex-col justify-between">
            <div>
                <!-- Category -->
                <a href="{{ route('shop.category', $product->category->slug) }}" class="inline-block text-xs text-primary-600 font-semibold uppercase tracking-wider hover:text-primary-700 transition mb-2">
                    {{ $product->category->translated_name ?? $product->category->name }}
                </a>
                
                <!-- Title -->
                <h3 class="mb-2">
                    <a href="{{ route('shop.show', $product->slug) }}" class="text-lg font-semibold text-gray-900 hover:text-primary-600 transition-colors">
                        {{ $product->translated_name }}
                    </a>
                </h3>
                
                <!-- Description -->
                @if($product->short_description)
                    <p class="text-gray-500 text-sm line-clamp-2 mb-3">{{ $product->short_description }}</p>
                @endif
                
                <!-- Rating -->
                <div class="flex items-center gap-2 mb-3">
                    @if($product->review_count > 0)
                        <div class="flex text-amber-400 text-sm gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($product->average_rating))
                                    <i class="fas fa-star"></i>
                                @elseif($i - 0.5 <= $product->average_rating)
                                    <i class="fas fa-star-half-alt"></i>
                                @else
                                    <i class="far fa-star text-gray-200"></i>
                                @endif
                            @endfor
                        </div>
                        <span class="text-sm text-gray-500">({{ $product->review_count }} {{ __('messages.reviews') ?? 'reviews' }})</span>
                    @else
                        <div class="flex text-gray-200 text-sm gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="far fa-star"></i>
                            @endfor
                        </div>
                        <span class="text-sm text-gray-400">{{ __('messages.no_reviews') ?? 'No reviews yet' }}</span>
                    @endif
                </div>
            </div>
            
            <!-- Price & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-3 border-t border-gray-100">
                <div class="flex items-baseline gap-3">
                    @if($product->sale_price)
                        <span class="text-2xl font-bold text-primary-600">{{ format_currency($product->sale_price) }}</span>
                        <span class="text-base text-gray-400 line-through">{{ format_currency($product->price) }}</span>
                    @else
                        <span class="text-2xl font-bold text-gray-900">{{ format_currency($product->price) }}</span>
                    @endif
                    
                    @if(!$product->isInStock())
                        <span class="text-xs text-rose-600 font-semibold bg-rose-50 px-2 py-1 rounded-lg">{{ __('messages.out_of_stock') ?? 'Out of Stock' }}</span>
                    @else
                        <span class="text-xs text-emerald-600 font-medium flex items-center">
                            <i class="fas fa-check-circle mr-1"></i>{{ __('messages.in_stock') ?? 'In Stock' }}
                        </span>
                    @endif
                </div>
                
                <div class="flex items-center gap-2">
                    <form action="{{ route('cart.add') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit" class="px-5 py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 transition-all flex items-center gap-2 shadow-lg shadow-primary-600/20">
                            <i class="fas fa-shopping-cart"></i>
                            {{ __('messages.add_to_cart') ?? 'Add to Cart' }}
                        </button>
                    </form>
                    
                    <a href="{{ route('shop.show', $product->slug) }}" class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center text-gray-600 hover:bg-gray-200 transition" title="{{ __('messages.view_details') }}">
                        <i class="fas fa-eye"></i>
                    </a>
                    
                    @auth
                        @module('wishlist')
                        <form action="{{ route('account.wishlist.add') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <button type="submit" class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center text-gray-600 hover:bg-rose-100 hover:text-rose-500 transition" title="{{ __('messages.add_to_wishlist') }}">
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
