@php
    // Mobile display settings (pixel values)
    $mobileTitlePx = setting('products.mobile_title_size_px', '13');
    $mobilePricePx = setting('products.mobile_price_px', '16');
    $mobileCategoryPx = setting('products.mobile_category_px', '10');
    $mobileTitleLines = setting('products.mobile_title_lines', '2');
    $mobileCardPaddingPx = setting('products.mobile_card_padding_px', '12');
    $mobileHideRating = setting('products.mobile_hide_rating', false);
    $mobileHideCategory = setting('products.mobile_hide_category', false);
    $mobileHideStock = setting('products.mobile_hide_stock', false);
    $mobileShowCartBtn = setting('products.mobile_show_cart_btn', true);
    $mobileImageRatio = setting('products.mobile_image_ratio', 'square');

    // Desktop display settings (pixel values)
    $desktopCardTitlePx = setting('products.desktop_card_title_px', '16');
    $desktopCardPricePx = setting('products.desktop_card_price_px', '18');
    $desktopTitleLines = setting('products.desktop_title_lines', '2');
    $titleCharLimit = (int) setting('products.title_char_limit', '0');
    $mobileTitleCharLimit = (int) setting('products.mobile_title_char_limit', '0');

    // Image aspect ratio class
    $imageRatioClass = match ($mobileImageRatio) {
        'portrait' => 'aspect-[3/4]',
        'landscape' => 'aspect-[4/3]',
        default => 'aspect-square',
    };

    // Mobile line clamp class
    $mobileLineClampClass = match ($mobileTitleLines) {
        '1' => 'line-clamp-1',
        '3' => 'line-clamp-3',
        '4' => 'line-clamp-4',
        default => 'line-clamp-2',
    };

    // Desktop line clamp class
    $desktopLineClampClass = match ($desktopTitleLines) {
        '1' => 'sm:line-clamp-1',
        '3' => 'sm:line-clamp-3',
        '4' => 'sm:line-clamp-4',
        default => 'sm:line-clamp-2',
    };

    // Calculate min-height for title based on lines (line-height ~1.375 for leading-snug)
    $mobileLineHeight = (int) $mobileTitlePx * 1.375;
    $mobileTitleMinHeight = $mobileLineHeight * (int) $mobileTitleLines;
    $desktopLineHeight = (int) $desktopCardTitlePx * 1.375;
    $desktopTitleMinHeight = $desktopLineHeight * (int) $desktopTitleLines;

    // Get display titles (with optional character truncation for desktop and mobile)
    $fullTitle = $product->translated_name;
    $desktopTitle = $fullTitle;
    $mobileTitle = $fullTitle;
    
    if ($titleCharLimit > 0 && mb_strlen($desktopTitle) > $titleCharLimit) {
        $desktopTitle = mb_substr($desktopTitle, 0, $titleCharLimit) . '...';
    }
    if ($mobileTitleCharLimit > 0 && mb_strlen($mobileTitle) > $mobileTitleCharLimit) {
        $mobileTitle = mb_substr($mobileTitle, 0, $mobileTitleCharLimit) . '...';
    }
@endphp
<style>
    /* Mobile typography */
    .product-card-title {
        font-size:
            {{ $mobileTitlePx }}
            px;
    }

    .product-card-price {
        font-size:
            {{ $mobilePricePx }}
            px;
    }

    .product-card-category {
        font-size:
            {{ $mobileCategoryPx }}
            px;
    }

    .product-card-padding {
        padding: {{ max((int) $mobileCardPaddingPx, 12) }}px {{ max((int) $mobileCardPaddingPx, 12) }}px {{ max((int) $mobileCardPaddingPx, 14) }}px;
    }

    /* Mobile title min-height for consistent alignment */
    .product-card-title-wrapper {
        min-height: {{ $mobileTitleMinHeight }}px;
    }

    /* Desktop typography */
    @media (min-width: 640px) {
        .product-card-title {
            font-size:
                {{ $desktopCardTitlePx }}
                px;
        }

        .product-card-price {
            font-size:
                {{ $desktopCardPricePx }}
                px;
        }

        .product-card-category {
            font-size: 0.75rem;
        }

        .product-card-padding {
            padding: 1.25rem 1.5rem 1rem;
        }

        .product-card-title-wrapper {
            min-height: {{ $desktopTitleMinHeight }}px;
        }
    }
</style>
<div
    class="group bg-white rounded-2xl hover:shadow-md transition-all duration-300 overflow-hidden border border-gray-200 hover:border-primary-300 relative h-full flex flex-col min-w-0">
    <!-- Image Container -->
    <div class="relative {{ $imageRatioClass }} sm:aspect-square overflow-hidden bg-white flex-shrink-0">
        <a href="{{ route('shop.show', $product->slug) }}" class="block w-full h-full">
            @if($product->image)
                <img src="{{ $product->image_url }}" alt="{{ $product->translated_name }}"
                    class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-500 ease-out"
                    loading="lazy"
                    width="300"
                    height="300"
                    style="max-width: 100%; height: auto;">
            @else
                <div class="w-full h-full flex items-center justify-center">
                    <i class="fas fa-image text-5xl text-gray-200"></i>
                </div>
            @endif
        </a>

        <!-- Badges -->
        <div class="absolute top-3 left-3 flex flex-col gap-1.5 z-10">
            @if($product->sale_price)
                <span class="bg-red-500 text-white text-[10px] sm:text-xs font-semibold px-2 py-1 rounded-md">
                    -{{ $product->discount_percent }}%
                </span>
            @endif
            @if($product->created_at && $product->created_at->diffInDays(now()) < 14)
                <span class="bg-primary-600 text-white text-[10px] sm:text-xs font-semibold px-2 py-1 rounded-md">
                    {{ __('messages.new') ?? 'NEW' }}
                </span>
            @endif
        </div>

        <!-- Featured Star -->
        @if($product->is_featured)
            <span
                class="absolute top-3 right-3 w-7 h-7 bg-amber-500 text-white rounded-md flex items-center justify-center z-10">
                <i class="fas fa-star text-[10px]"></i>
            </span>
        @endif

        <!-- Quick Actions (Desktop) -->
        <div
            class="absolute inset-x-0 bottom-0 p-3 bg-white/90 opacity-0 group-hover:opacity-100 transition-all duration-200 translate-y-2 group-hover:translate-y-0 hidden sm:block border-t border-gray-100">
            <div class="flex items-center justify-center gap-2">
                <form action="{{ route('cart.add') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit"
                        class="w-9 h-9 bg-white border border-gray-200 rounded-lg flex items-center justify-center text-gray-600 hover:bg-primary-600 hover:text-white hover:border-primary-600 transition-colors duration-200"
                        title="{{ __('messages.add_to_cart') }}">
                        <i class="fas fa-shopping-cart text-sm"></i>
                    </button>
                </form>
                <a href="{{ route('shop.show', $product->slug) }}"
                    class="w-9 h-9 bg-white border border-gray-200 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-800 hover:text-white hover:border-gray-800 transition-colors duration-200"
                    title="{{ __('messages.view_details') }}">
                    <i class="fas fa-eye text-sm"></i>
                </a>
                @auth
                    @module('wishlist')
                    <form action="{{ route('account.wishlist.add') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <button type="submit"
                            class="w-9 h-9 bg-white border border-gray-200 rounded-lg flex items-center justify-center text-gray-600 hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-colors duration-200"
                            title="{{ __('messages.add_to_wishlist') }}">
                            <i class="fas fa-heart text-sm"></i>
                        </button>
                    </form>
                    @endmodule
                @endauth
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="product-card-padding flex flex-col sm:flex-grow overflow-hidden">
        @if(!$mobileHideCategory)
            <a href="{{ route('shop.category', $product->category->slug) }}"
                class="inline-block product-card-category text-primary-600 font-semibold uppercase tracking-wider hover:text-primary-700 transition mb-2 truncate">
                {{ $product->category->translated_name ?? $product->category->name }}
            </a>
        @else
            <a href="{{ route('shop.category', $product->category->slug) }}"
                class="hidden sm:inline-block text-xs text-primary-600 font-semibold uppercase tracking-wider hover:text-primary-700 transition mb-2 truncate">
                {{ $product->category->translated_name ?? $product->category->name }}
            </a>
        @endif

        <!-- Title -->
        <h3 class="mb-1 sm:mb-2 min-w-0 product-card-title-wrapper">
            <a href="{{ route('shop.show', $product->slug) }}"
                class="font-semibold text-gray-900 hover:text-primary-600 transition-colors {{ $mobileLineClampClass }} {{ $desktopLineClampClass }} product-card-title leading-snug block"
                title="{{ $fullTitle }}">
                <span class="sm:hidden">{{ $mobileTitle }}</span>
                <span class="hidden sm:inline">{{ $desktopTitle }}</span>
            </a>
        </h3>

        @if(!$mobileHideRating)
            <div class="flex items-center gap-1.5 mb-1 {{ $product->review_count > 0 ? '' : 'hidden sm:flex' }}">
                @if($product->review_count > 0)
                    <div class="flex text-amber-400 text-xs gap-0.5">
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
                    <span class="text-[10px] sm:text-xs text-gray-500">({{ $product->review_count }})</span>
                @else
                    <div class="flex text-gray-200 text-xs gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="far fa-star"></i>
                        @endfor
                    </div>
                    <span class="text-xs text-gray-400">{{ __('messages.no_reviews') ?? 'No reviews' }}</span>
                @endif
            </div>
        @else
            <div class="hidden sm:flex items-center gap-1.5 mb-1">
                @if($product->review_count > 0)
                    <div class="flex text-amber-400 text-xs gap-0.5">
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
                    <span class="text-xs text-gray-500">({{ $product->review_count }})</span>
                @endif
            </div>
        @endif

        <!-- Price section - compact on mobile, pushed to bottom on desktop -->
        <div class="sm:mt-auto min-w-0 mt-1">
            <div class="flex items-end justify-between gap-2">
                <div class="min-w-0 flex-1">
                    @if($product->sale_price)
                        <div class="flex items-baseline gap-1 sm:gap-2 flex-wrap">
                            <span
                                class="product-card-price font-bold text-primary-600 whitespace-nowrap">{{ format_currency($product->sale_price) }}</span>
                            <span
                                class="text-[10px] sm:text-sm text-gray-400 line-through whitespace-nowrap">{{ format_currency($product->price) }}</span>
                        </div>
                    @else
                        <span
                            class="product-card-price font-bold text-gray-900 whitespace-nowrap">{{ format_currency($product->price) }}</span>
                    @endif
                </div>
                @if(!$mobileHideStock)
                    @if(!$product->isInStock())
                        <span
                            class="text-[10px] sm:text-xs text-rose-600 font-semibold bg-rose-50 px-2 py-1 rounded-lg whitespace-nowrap flex-shrink-0">{{ __('messages.out_of_stock') ?? 'Out of Stock' }}</span>
                    @else
                        <span
                            class="hidden sm:inline-flex items-center text-xs text-emerald-600 font-medium whitespace-nowrap flex-shrink-0">
                            <i class="fas fa-check-circle mr-1"></i>{{ __('messages.in_stock') ?? 'In Stock' }}
                        </span>
                    @endif
                @endif
            </div>

            @if($mobileShowCartBtn)
                <form action="{{ route('cart.add') }}" method="POST" class="mt-4 sm:hidden">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit"
                        class="w-full py-2.5 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 active:scale-[0.98] transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-shopping-cart"></i>
                        {{ __('messages.add_to_cart') ?? 'Add to Cart' }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>