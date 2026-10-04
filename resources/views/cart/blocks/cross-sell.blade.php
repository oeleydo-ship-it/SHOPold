{{-- Cart page: "You may also like" products. Shared by every theme; replace at themes/<slug>/cart/blocks/cross-sell.blade.php --}}
            <!-- Cross-Sell Products -->
            @if(isset($crossSellProducts) && $crossSellProducts->count() > 0)
            <div class="mt-8">
                <h3 class="text-xl font-bold mb-4">You May Also Like</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($crossSellProducts as $crossSellProduct)
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden group">
                        <a href="{{ route('shop.show', $crossSellProduct->slug) }}" class="block aspect-square overflow-hidden">
                            @if($crossSellProduct->image_url)
                                <img src="{{ $crossSellProduct->image_url }}" alt="{{ $crossSellProduct->translated_name ?? $crossSellProduct->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            @else
                                <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-3xl"></i>
                                </div>
                            @endif
                        </a>
                        <div class="p-4">
                            <a href="{{ route('shop.show', $crossSellProduct->slug) }}" class="font-semibold text-gray-900 hover:text-primary-600 line-clamp-2 text-sm">
                                {{ $crossSellProduct->translated_name ?? $crossSellProduct->name }}
                            </a>
                            <div class="flex items-center gap-2 mt-2">
                                @if($crossSellProduct->sale_price && $crossSellProduct->sale_price < $crossSellProduct->price)
                                    <span class="text-primary-600 font-bold">{{ format_currency($crossSellProduct->sale_price) }}</span>
                                    <span class="text-gray-400 text-sm line-through">{{ format_currency($crossSellProduct->price) }}</span>
                                @else
                                    <span class="text-primary-600 font-bold">{{ format_currency($crossSellProduct->price) }}</span>
                                @endif
                            </div>
                            @if($crossSellProduct->product_type === 'variable')
                                <a href="{{ route('shop.show', $crossSellProduct->slug) }}" class="mt-3 w-full block text-center py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition text-sm">
                                    <i class="fas fa-eye mr-1"></i>View Options
                                </a>
                            @else
                                <form action="{{ route('cart.add') }}" method="POST" class="mt-3">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $crossSellProduct->id }}">
                                    <button type="submit" class="w-full py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition text-sm">
                                        <i class="fas fa-cart-plus mr-1"></i>Add to Cart
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
