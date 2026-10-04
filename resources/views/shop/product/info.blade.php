{{-- Product page: Title, rating, price, sale countdown, variations, quantity / add to cart / buy now, wishlist & compare, meta, share. Shared by every theme; a theme can replace it at themes/<slug>/shop/product/info.blade.php --}}
            <!-- Product Info -->
            <div>
                @php
                    // Shop typography settings
                    $productTitleFont = setting('shop.product_title_font', '');
                    $productTitleWeight = setting('shop.product_title_weight', '700');
                    $productTitleSize = setting('shop.product_title_size', '2xl');
                    $productTitleColor = setting('shop.product_title_color', '#1f2937');
                    $priceSize = setting('shop.price_size', '2xl');
                    $priceColor = setting('shop.price_color', '#1f2937');
                    $salePriceColor = setting('shop.sale_price_color', '#dc2626');
                    $brandStyle = setting('shop.brand_style', 'uppercase');
                    $brandColor = setting('shop.brand_color', '#2563eb');
                    
                    // Size mappings
                    $titleSizeMap = ['xl' => '20px', '2xl' => '24px', '3xl' => '30px', '4xl' => '36px'];
                    $priceSizeMap = ['lg' => '18px', 'xl' => '20px', '2xl' => '24px', '3xl' => '30px'];
                    $titlePx = $titleSizeMap[$productTitleSize] ?? '24px';
                    $pricePx = $priceSizeMap[$priceSize] ?? '24px';
                    
                    // Mobile typography settings (fallback)
                    $mobileDetailTitlePx = setting('products.mobile_detail_title_px', '20');
                    $mobileShortDescPx = setting('products.mobile_short_desc_px', '14');
                    $mobileLongDescPx = setting('products.mobile_long_desc_px', '14');
                    $mobilePricePx = setting('products.mobile_price_px', '16');
                    
                    // Desktop typography settings (fallback)
                    $desktopTitlePx = setting('products.desktop_title_px', '28');
                    $desktopShortDescPx = setting('products.desktop_short_desc_px', '16');
                    $desktopLongDescPx = setting('products.desktop_long_desc_px', '16');
                    $desktopPricePx = setting('products.desktop_price_px', '24');
                @endphp
                
                @if($productTitleFont)
                <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $productTitleFont) }}:wght@400;500;600;700;800&display=swap" rel="stylesheet">
                @endif
                
                <style>
                    /* Product title styling from shop settings */
                    .product-detail-title {
                        @if($productTitleFont)
                        font-family: '{{ $productTitleFont }}', sans-serif !important;
                        @endif
                        font-weight: {{ $productTitleWeight }} !important;
                        color: {{ $productTitleColor }} !important;
                    }
                    
                    /* Brand/Category styling */
                    .product-brand-link {
                        color: {{ $brandColor }};
                        text-transform: {{ $brandStyle }};
                    }
                    
                    /* Price styling */
                    .product-price-main {
                        color: {{ $priceColor }};
                    }
                    .product-price-sale {
                        color: {{ $salePriceColor }};
                    }
                    
                    /* Mobile typography */
                    .product-detail-title { font-size: {{ $mobileDetailTitlePx }}px; }
                    .product-short-desc { font-size: {{ $mobileShortDescPx }}px; }
                    .product-long-desc { font-size: {{ $mobileLongDescPx }}px; }
                    .product-price { font-size: {{ $mobilePricePx }}px; }
                    
                    /* Desktop typography */
                    @media (min-width: 768px) {
                        .product-detail-title { font-size: {{ $titlePx }}; }
                        .product-short-desc { font-size: {{ $desktopShortDescPx }}px; }
                        .product-long-desc { font-size: {{ $desktopLongDescPx }}px; }
                        .product-price { font-size: {{ $pricePx }}; }
                    }
                </style>
                
                <!-- Brand & Category -->
                @if(setting('products.show_brand', true) || setting('products.show_category', true))
                <div class="flex items-center gap-3 mb-2">
                    @if(setting('products.show_brand', true) && $product->brand)
                        <a href="{{ route('shop.index', ['brand' => $product->brand->slug]) }}" class="text-sm product-brand-link font-medium tracking-wide">
                            {{ $product->brand->translated_name }}
                        </a>
                        @if(setting('products.show_category', true))
                        <span class="text-gray-300">|</span>
                        @endif
                    @endif
                    @if(setting('products.show_category', true))
                    <a href="{{ route('shop.category', $product->category->slug) }}" class="text-sm product-brand-link font-medium tracking-wide">
                        {{ $product->category->translated_name }}
                    </a>
                    @endif
                </div>
                @endif
                
                <h1 class="product-detail-title mt-2 mb-4">{{ $product->translated_name }}</h1>
                
                <!-- Tags -->
                @if(setting('products.show_tags', true) && $product->tags->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($product->tags as $tag)
                        <a href="{{ route('shop.index', ['tag' => $tag->slug]) }}" 
                           class="px-2 py-1 text-xs rounded-full transition hover:opacity-80"
                           style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}40;">
                            {{ $tag->name }}
                        </a>
                    @endforeach
                </div>
                @endif
                
                @if($product->review_count > 0)
                <div class="flex items-center mb-4">
                    <div class="flex text-yellow-400">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star{{ $i <= round($product->average_rating) ? '' : ' text-gray-300' }}"></i>
                        @endfor
                    </div>
                    <span class="text-gray-600 ml-2">({{ $product->review_count }} reviews)</span>
                </div>
                @endif

                <div class="mb-6">
                    @if($product->isOnSale())
                        <span class="product-price font-bold product-price-sale">{{ format_currency($product->sale_price) }}</span>
                        <span class="text-lg md:text-xl text-gray-400 line-through ml-2">{{ format_currency($product->price) }}</span>
                        <span class="ml-2 px-3 py-1 bg-red-100 text-red-600 text-sm font-semibold rounded-full">Save {{ $product->discount_percent }}%</span>
                        
                        <!-- Sale Countdown -->
                        @if($product->sale_end_date)
                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg" x-data="saleCountdown('{{ $product->sale_end_date->toIso8601String() }}')">
                            <div class="flex items-center text-red-700">
                                <i class="fas fa-clock mr-2"></i>
                                <span class="font-medium">Sale ends in:</span>
                                <div class="flex items-center ml-3 space-x-2">
                                    <span class="bg-red-600 text-white px-2 py-1 rounded font-mono" x-text="days + 'd'"></span>
                                    <span class="bg-red-600 text-white px-2 py-1 rounded font-mono" x-text="hours + 'h'"></span>
                                    <span class="bg-red-600 text-white px-2 py-1 rounded font-mono" x-text="minutes + 'm'"></span>
                                    <span class="bg-red-600 text-white px-2 py-1 rounded font-mono" x-text="seconds + 's'"></span>
                                </div>
                            </div>
                        </div>
                        @endif
                    @else
                        <span class="product-price font-bold product-price-main">{{ format_currency($product->price) }}</span>
                    @endif
                </div>

                <p class="text-gray-600 mb-6 product-short-desc leading-relaxed">{!! e($product->translated_short_description) ?: Str::limit(strip_tags(clean_html($product->translated_description)), 200) !!}</p>

                <div class="flex items-center gap-4 mb-6">
                    <span class="text-gray-600">Availability:</span>
                    @if($product->isInStock())
                        @if($product->product_type === 'variable')
                            <span class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>In Stock</span>
                        @elseif(setting('products.show_stock_quantity', false))
                            <span class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>In Stock ({{ $product->quantity }} available)</span>
                        @else
                            <span class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>In Stock</span>
                        @endif
                    @else
                        <span class="text-red-600 font-medium"><i class="fas fa-times-circle mr-1"></i>Out of Stock</span>
                    @endif
                    
                    @if($product->is_downloadable)
                        <span class="text-purple-600 font-medium"><i class="fas fa-download mr-1"></i>Digital Product</span>
                    @endif
                </div>

                @if(setting('products.show_sku', true) && $product->sku)
                <div class="mb-6">
                    <span class="text-gray-600">SKU:</span>
                    {{-- this label sits outside the variation picker's Alpine scope, so guard the lookup --}}
                    <span class="font-medium ml-2" x-text="(typeof selectedVariation !== 'undefined' && selectedVariation) ? selectedVariation.sku : '{{ $product->sku }}'">{{ $product->sku }}</span>
                </div>
                @endif

                @if($product->product_type === 'variable' && $product->variations->count() > 0)
                <!-- Variation Selection -->
                <div class="mb-6" x-data="variationSelector()">
                    @php
                        // Group variations by attribute
                        $attributeOptions = [];
                        foreach ($product->variations as $variation) {
                            foreach ($variation->attributeValues as $attrValue) {
                                $attrName = $attrValue->attribute->name;
                                if (!isset($attributeOptions[$attrName])) {
                                    $attributeOptions[$attrName] = [
                                        'attribute' => $attrValue->attribute,
                                        'values' => []
                                    ];
                                }
                                $attributeOptions[$attrName]['values'][$attrValue->id] = $attrValue;
                            }
                        }
                    @endphp

                    @foreach($attributeOptions as $attrName => $data)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $attrName }}</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($data['values'] as $valueId => $value)
                                @if($data['attribute']->type === 'color')
                                <button type="button"
                                        @click="selectAttribute('{{ $attrName }}', {{ $valueId }})"
                                        :class="selectedAttributes['{{ $attrName }}'] === {{ $valueId }} ? 'ring-2 ring-primary-500 ring-offset-2' : ''"
                                        class="w-10 h-10 rounded-full border-2 border-gray-300 transition"
                                        style="background-color: {{ $value->color_code ?? '#ccc' }}"
                                        title="{{ $value->value }}">
                                </button>
                                @else
                                <button type="button"
                                        @click="selectAttribute('{{ $attrName }}', {{ $valueId }})"
                                        :class="selectedAttributes['{{ $attrName }}'] === {{ $valueId }} ? 'bg-primary-600 text-white border-primary-600' : 'bg-white text-gray-700 border-gray-300 hover:border-primary-500'"
                                        class="px-4 py-2 border rounded-lg font-medium transition">
                                    {{ $value->value }}
                                </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endforeach

                    <!-- Selected Variation Info -->
                    <div x-show="selectedVariation" class="p-3 bg-gray-50 rounded-lg mb-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Selected:</span>
                            <span class="font-medium" x-text="selectedVariation?.variation_name || 'Select options'"></span>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-gray-600">Price:</span>
                            <span class="font-bold text-primary-600" x-text="selectedVariation ? formatCurrency(selectedVariation.price || {{ $product->price }}) : ''"></span>
                        </div>
                        @if(setting('products.show_stock_quantity', false))
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-gray-600">Stock:</span>
                            <span :class="selectedVariation?.quantity > 0 ? 'text-green-600' : 'text-red-600'" x-text="selectedVariation ? (selectedVariation.quantity > 0 ? selectedVariation.quantity + ' available' : 'Out of stock') : ''"></span>
                        </div>
                        @else
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-gray-600">Stock:</span>
                            <span :class="selectedVariation?.quantity > 0 ? 'text-green-600' : 'text-red-600'" x-text="selectedVariation ? (selectedVariation.quantity > 0 ? 'In Stock' : 'Out of stock') : ''"></span>
                        </div>
                        @endif
                    </div>

                    <input type="hidden" name="variation_id" :value="selectedVariation?.id || ''">
                    
                    @php
                        // Check if custom button colors are enabled
                        $useCustomColors = setting('products.use_custom_button_colors', false);
                        
                        // Get button colors from settings (only used if custom colors enabled)
                        $atcBgColor = $useCustomColors ? setting('products.add_to_cart_bg_color', '#ffffff') : null;
                        $atcTextColor = $useCustomColors ? setting('products.add_to_cart_text_color', '#2563eb') : null;
                        $atcBorderColor = $useCustomColors ? setting('products.add_to_cart_border_color', '#2563eb') : null;
                        $atcHoverBg = $useCustomColors ? setting('products.add_to_cart_hover_bg', '#eff6ff') : null;
                        $buyNowBgColor = $useCustomColors ? setting('products.buy_now_bg_color', '#2563eb') : null;
                        $buyNowTextColor = $useCustomColors ? setting('products.buy_now_text_color', '#ffffff') : null;
                        $buyNowHoverBg = $useCustomColors ? setting('products.buy_now_hover_bg', '#1d4ed8') : null;
                    @endphp
                    
                    <!-- Quantity and Add to Cart for Variable Products -->
                    <div class="mt-4" id="inline-cart-buttons">
                        <label class="block text-sm text-gray-600 mb-2">Quantity</label>
                        <div class="flex items-center border border-gray-300 rounded-md w-fit mb-6">
                            <button type="button" onclick="decrementQty()" class="px-4 py-3 text-gray-400 hover:text-gray-600 transition">
                                <span class="text-lg">−</span>
                            </button>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" :max="selectedVariation?.quantity || {{ $product->quantity }}" class="w-12 text-center border-0 focus:ring-0 text-gray-800 font-medium">
                            <button type="button" onclick="incrementQty()" class="px-4 py-3 text-gray-400 hover:text-gray-600 transition">
                                <span class="text-lg">+</span>
                            </button>
                        </div>
                        
                        @if(setting('products.show_add_to_cart', true))
                        <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form mb-3" @submit.prevent="submitAddToCart($event, $el)">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="variation_id" x-bind:value="selectedVariation?.id || ''">
                            <input type="hidden" name="quantity" class="qty-input" value="1">
                            @if($useCustomColors)
                            <button type="submit" 
                                    :disabled="!selectedVariation || submitting"
                                    class="w-full py-3.5 border-2 font-medium rounded-md transition add-to-cart-btn"
                                    :class="!selectedVariation || submitting ? 'opacity-50 cursor-not-allowed' : ''"
                                    style="background-color: {{ $atcBgColor }}; color: {{ $atcTextColor }}; border-color: {{ $atcBorderColor }};"
                                    onmouseover="if(!this.disabled) this.style.backgroundColor='{{ $atcHoverBg }}'"
                                    onmouseout="this.style.backgroundColor='{{ $atcBgColor }}'">
                                <span x-text="submitting ? 'Adding...' : 'Add to cart'"></span>
                            </button>
                            @else
                            <button type="submit" 
                                    :disabled="!selectedVariation || submitting"
                                    :class="selectedVariation && !submitting ? 'border-primary-600 text-primary-600 hover:bg-primary-50' : 'border-gray-300 text-gray-400 cursor-not-allowed'"
                                    class="w-full py-3.5 border-2 font-medium rounded-md transition">
                                <span x-text="submitting ? 'Adding...' : 'Add to cart'"></span>
                            </button>
                            @endif
                        </form>
                        @endif
                        
                        @if(setting('products.show_buy_now', true))
                        <form action="{{ route('cart.buy-now') }}" method="POST" class="buy-now-form" @submit="submitBuyNow($event, $el)">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="variation_id" x-bind:value="selectedVariation?.id || ''">
                            <input type="hidden" name="quantity" class="qty-input" value="1">
                            @if($useCustomColors)
                            <button type="submit" 
                                    :disabled="!selectedVariation || buyingNow"
                                    class="w-full py-3.5 font-medium rounded-md transition buy-now-btn"
                                    :class="!selectedVariation || buyingNow ? 'opacity-50 cursor-not-allowed' : ''"
                                    style="background-color: {{ $buyNowBgColor }}; color: {{ $buyNowTextColor }};"
                                    onmouseover="if(!this.disabled) this.style.backgroundColor='{{ $buyNowHoverBg }}'"
                                    onmouseout="this.style.backgroundColor='{{ $buyNowBgColor }}'">
                                <span x-text="buyingNow ? 'Processing...' : 'Buy it now'"></span>
                            </button>
                            @else
                            <button type="submit" 
                                    :disabled="!selectedVariation || buyingNow"
                                    :class="selectedVariation && !buyingNow ? 'bg-primary-600 hover:bg-primary-700' : 'bg-gray-400 cursor-not-allowed'"
                                    class="w-full py-3.5 text-white font-medium rounded-md transition">
                                <span x-text="buyingNow ? 'Processing...' : 'Buy it now'"></span>
                            </button>
                            @endif
                        </form>
                        @endif
                    </div>
                </div>
                @else
                @if($product->isInStock())
                @php
                    // Check if custom button colors are enabled (for simple products)
                    $useCustomColors = setting('products.use_custom_button_colors', false);
                    
                    // Get button colors from settings
                    $atcBgColor = $useCustomColors ? setting('products.add_to_cart_bg_color', '#ffffff') : null;
                    $atcTextColor = $useCustomColors ? setting('products.add_to_cart_text_color', '#2563eb') : null;
                    $atcBorderColor = $useCustomColors ? setting('products.add_to_cart_border_color', '#2563eb') : null;
                    $atcHoverBg = $useCustomColors ? setting('products.add_to_cart_hover_bg', '#eff6ff') : null;
                    $buyNowBgColor = $useCustomColors ? setting('products.buy_now_bg_color', '#2563eb') : null;
                    $buyNowTextColor = $useCustomColors ? setting('products.buy_now_text_color', '#ffffff') : null;
                    $buyNowHoverBg = $useCustomColors ? setting('products.buy_now_hover_bg', '#1d4ed8') : null;
                @endphp
                
                <!-- Quantity and Add to Cart for Simple Products -->
                <div class="mb-4" id="inline-cart-buttons-simple" x-data="{ submitting: false, buyingNow: false }">
                    <label class="block text-sm text-gray-600 mb-2">Quantity</label>
                    <div class="flex items-center border border-gray-300 rounded-md w-fit mb-6">
                        <button type="button" onclick="decrementQty()" class="px-4 py-3 text-gray-400 hover:text-gray-600 transition">
                            <span class="text-lg">−</span>
                        </button>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" max="{{ $product->quantity }}" class="w-12 text-center border-0 focus:ring-0 text-gray-800 font-medium">
                        <button type="button" onclick="incrementQty()" class="px-4 py-3 text-gray-400 hover:text-gray-600 transition">
                            <span class="text-lg">+</span>
                        </button>
                    </div>
                    
                    @if(setting('products.show_add_to_cart', true))
                    <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-form mb-3" @submit.prevent="submitSimpleAddToCart($event, $el)">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="quantity" class="qty-input" value="1">
                        @if($useCustomColors)
                        <button type="submit" 
                                :disabled="submitting"
                                class="w-full py-3.5 border-2 font-medium rounded-md transition add-to-cart-btn"
                                :class="submitting ? 'opacity-50 cursor-not-allowed' : ''"
                                style="background-color: {{ $atcBgColor }}; color: {{ $atcTextColor }}; border-color: {{ $atcBorderColor }};"
                                onmouseover="if(!this.disabled) this.style.backgroundColor='{{ $atcHoverBg }}'"
                                onmouseout="this.style.backgroundColor='{{ $atcBgColor }}'">
                            <span x-text="submitting ? 'Adding...' : 'Add to cart'"></span>
                        </button>
                        @else
                        <button type="submit" 
                                :disabled="submitting"
                                :class="submitting ? 'border-gray-300 text-gray-400 cursor-not-allowed' : 'border-primary-600 text-primary-600 hover:bg-primary-50'"
                                class="w-full py-3.5 border-2 font-medium rounded-md transition">
                            <span x-text="submitting ? 'Adding...' : 'Add to cart'"></span>
                        </button>
                        @endif
                    </form>
                    @endif
                    
                    @if(setting('products.show_buy_now', true))
                    <form action="{{ route('cart.buy-now') }}" method="POST" class="buy-now-form" @submit="submitSimpleBuyNow($event, $el)">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="quantity" class="qty-input" value="1">
                        @if($useCustomColors)
                        <button type="submit" 
                                :disabled="buyingNow"
                                class="w-full py-3.5 font-medium rounded-md transition buy-now-btn"
                                :class="buyingNow ? 'opacity-50 cursor-not-allowed' : ''"
                                style="background-color: {{ $buyNowBgColor }}; color: {{ $buyNowTextColor }};"
                                onmouseover="if(!this.disabled) this.style.backgroundColor='{{ $buyNowHoverBg }}'"
                                onmouseout="this.style.backgroundColor='{{ $buyNowBgColor }}'">
                            <span x-text="buyingNow ? 'Processing...' : 'Buy it now'"></span>
                        </button>
                        @else
                        <button type="submit" 
                                :disabled="buyingNow"
                                :class="buyingNow ? 'bg-gray-400 cursor-not-allowed' : 'bg-primary-600 hover:bg-primary-700'"
                                class="w-full py-3.5 text-white font-medium rounded-md transition">
                            <span x-text="buyingNow ? 'Processing...' : 'Buy it now'"></span>
                        </button>
                        @endif
                    </form>
                    @endif
                </div>
                @endif
                @endif

                <!-- Compare & Wishlist Buttons -->
                @if(setting('products.show_compare', true) || setting('products.show_wishlist', true))
                <div class="flex items-center gap-4 mb-6 text-sm">
                    @if(setting('products.show_compare', true))
                    <button type="button" onclick="addToCompare({{ $product->id }})" class="flex items-center gap-2 text-gray-600 hover:text-primary-600 transition">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Compare</span>
                    </button>
                    @endif
                    
                    @if(setting('products.show_wishlist', true))
                    @auth
                    @module('wishlist')
                    <form action="{{ route('account.wishlist.add') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit" class="flex items-center gap-2 text-gray-600 hover:text-red-500 transition">
                            <i class="far fa-heart"></i>
                            <span>Add Wishlist</span>
                        </button>
                    </form>
                    @endmodule
                    @else
                    <a href="{{ route('login') }}" class="flex items-center gap-2 text-gray-600 hover:text-red-500 transition">
                        <i class="far fa-heart"></i>
                        <span>Add Wishlist</span>
                    </a>
                    @endauth
                    @endif
                </div>
                @endif

                <!-- Product Meta Info -->
                <div class="border-t pt-4 space-y-2 text-sm">
                    @if(setting('products.show_sku', true) && $product->sku)
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 font-medium">SKU:</span>
                        <span class="text-gray-700" x-text="(typeof selectedVariation !== 'undefined' && selectedVariation) ? selectedVariation.sku : '{{ $product->sku }}'">{{ $product->sku }}</span>
                    </div>
                    @endif
                    
                    @if(setting('products.show_category', true))
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 font-medium">Category:</span>
                        <a href="{{ route('shop.category', $product->category->slug) }}" class="text-gray-700 hover:text-primary-600">{{ $product->category->translated_name }}</a>
                    </div>
                    @endif
                    
                    @if(setting('products.show_tags', true) && $product->tags->isNotEmpty())
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 font-medium">Tag:</span>
                        <span class="text-gray-700">
                            @foreach($product->tags as $tag)
                                <a href="{{ route('shop.index', ['tag' => $tag->slug]) }}" class="hover:text-primary-600">{{ $tag->name }}</a>@if(!$loop->last), @endif
                            @endforeach
                        </span>
                    </div>
                    @endif
                </div>

                <!-- Social Share Buttons -->
                @if(setting('products.show_share', true))
                <div class="border-t pt-4 mt-4">
                    <div class="flex items-center gap-3">
                        <span class="text-gray-500 font-medium text-sm">Share:</span>
                        <div class="flex items-center gap-2">
                            @if(setting('products.share_facebook', true))
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank" rel="noopener" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-blue-600 hover:text-white transition" title="Share on Facebook">
                                <i class="fab fa-facebook-f text-sm"></i>
                            </a>
                            @endif
                            
                            @if(setting('products.share_twitter', true))
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($product->translated_name) }}" target="_blank" rel="noopener" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-black hover:text-white transition" title="Share on X">
                                <i class="fab fa-x-twitter text-sm"></i>
                            </a>
                            @endif
                            
                            @if(setting('products.share_whatsapp', true))
                            <a href="https://wa.me/?text={{ urlencode($product->translated_name . ' ' . request()->url()) }}" target="_blank" rel="noopener" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-green-500 hover:text-white transition" title="Share on WhatsApp">
                                <i class="fab fa-whatsapp text-sm"></i>
                            </a>
                            @endif
                            
                            @if(setting('products.share_pinterest', true))
                            <a href="https://pinterest.com/pin/create/button/?url={{ urlencode(request()->url()) }}&media={{ urlencode($product->image_url) }}&description={{ urlencode($product->translated_name) }}" target="_blank" rel="noopener" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-red-600 hover:text-white transition" title="Share on Pinterest">
                                <i class="fab fa-pinterest-p text-sm"></i>
                            </a>
                            @endif
                            
                            @if(setting('products.share_linkedin', false))
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(request()->url()) }}&title={{ urlencode($product->translated_name) }}" target="_blank" rel="noopener" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-blue-700 hover:text-white transition" title="Share on LinkedIn">
                                <i class="fab fa-linkedin-in text-sm"></i>
                            </a>
                            @endif
                            
                            @if(setting('products.share_email', true))
                            <a href="mailto:?subject={{ urlencode($product->translated_name) }}&body={{ urlencode('Check out this product: ' . request()->url()) }}" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-gray-700 hover:text-white transition" title="Share via Email">
                                <i class="fas fa-envelope text-sm"></i>
                            </a>
                            @endif
                            
                            @if(setting('products.share_copy', true))
                            <button type="button" onclick="copyProductLink()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-primary-600 hover:text-white transition" title="Copy Link">
                                <i class="fas fa-link text-sm"></i>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>
