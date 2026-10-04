@if(($appSettings['homepage']['show_featured'] ?? $appSettings['theme']['show_featured'] ?? true) && $featuredProducts->count() > 0)
@php
    $featuredLabel = $appSettings['homepage']['featured_label'] ?? __('messages.handpicked');
    $featuredTitle = $appSettings['homepage']['featured_title'] ?? __('messages.featured_products');
    $featuredButton = $appSettings['homepage']['featured_button'] ?? __('messages.view_all');
@endphp
<section class="py-12 md:py-20 homepage-body" style="{{ $featuredBgColor ? 'background-color: ' . $featuredBgColor . ';' : '' }}">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8 md:mb-12">
            <div>
                <span class="inline-flex items-center px-3 py-1 bg-primary-100 text-primary-700 text-xs font-semibold uppercase tracking-wider rounded-full mb-2">
                    <i class="fas fa-star mr-1.5"></i>{{ $featuredLabel }}
                </span>
                <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 homepage-heading">{{ $featuredTitle }}</h2>
            </div>
            <a href="{{ route('shop.index') }}" class="hidden md:inline-flex items-center px-6 py-3 border-2 border-primary-600 text-primary-600 font-semibold rounded-xl hover:bg-primary-600 hover:text-white transition-all duration-300 group">
                {{ $featuredButton }}
                <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
        
        @php
            $homepageMobileColumns = setting('products.homepage_mobile_columns', '2');
            $homepageMobileScroll = setting('products.homepage_mobile_scroll', false);
            $homepageMobileCompact = setting('products.homepage_mobile_compact', false);
            $homepageMobileCount = (int) setting('products.homepage_mobile_count', '4');
            $mobileGapPx = setting('products.mobile_card_gap_px', '12');
            $mobileColClass = $homepageMobileColumns === '1' ? 'grid-cols-1' : 'grid-cols-2';
        @endphp
        
        <!-- Products Grid -->
        @if($homepageMobileScroll)
        {{-- Horizontal Scroll on Mobile --}}
        <div class="md:hidden overflow-x-auto pb-4 -mx-4 px-4 scrollbar-hide">
            <div class="flex gap-3" style="gap: {{ $mobileGapPx }}px;">
                @foreach($featuredProducts->take($homepageMobileCount) as $product)
                    <div class="flex-shrink-0 w-[160px]">
                        @include('components.product-card', ['product' => $product])
                    </div>
                @endforeach
            </div>
        </div>
        <div class="hidden md:grid md:grid-cols-3 lg:grid-cols-4 gap-6" style="grid-auto-rows: 1fr;">
            @foreach($featuredProducts as $product)
                <div class="h-full">
                    @include('components.product-card', ['product' => $product])
                </div>
            @endforeach
        </div>
        @else
        {{-- Grid Layout --}}
        <div class="grid {{ $mobileColClass }} md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-6" style="--mobile-gap: {{ $mobileGapPx }}px; grid-auto-rows: 1fr;">
            <style>.featured-grid { gap: var(--mobile-gap); } @media (min-width: 768px) { .featured-grid { gap: 1.5rem; } }</style>
            @foreach($featuredProducts->take($homepageMobileCount) as $index => $product)
                <div class="h-full {{ $index >= $homepageMobileCount ? 'hidden md:block' : '' }}">
                    @include('components.product-card', ['product' => $product])
                </div>
            @endforeach
            @foreach($featuredProducts->skip($homepageMobileCount) as $product)
                <div class="hidden md:block h-full">
                    @include('components.product-card', ['product' => $product])
                </div>
            @endforeach
        </div>
        @endif
        
        <!-- Mobile View All Button -->
        <div class="text-center mt-8 md:hidden">
            <a href="{{ route('shop.index') }}" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white font-semibold rounded-xl hover:bg-primary-700 transition-all duration-300 shadow-lg group">
                {{ $featuredButton }}
                <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
    </div>
</section>
@endif
