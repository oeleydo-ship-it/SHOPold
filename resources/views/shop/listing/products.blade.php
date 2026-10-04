{{-- Shop page: Product grid / list, pagination and the empty state. Shared by every theme; replace at themes/<slug>/shop/listing/products.blade.php --}}
                @if($products->count() > 0)
                    @php
                        $mobileColumns = setting('products.mobile_columns', '2');
                        $mobileColClass = $mobileColumns === '1' ? 'grid-cols-1' : 'grid-cols-2';
                        $mobileGapPx = setting('products.mobile_card_gap_px', '8');
                        $initialGridCols = in_array((string) ($gridColumns ?? ''), ['2', '3', '4'], true) ? (string) $gridColumns : '4';
                        $initialMobileCols = $mobileColumns === '1' ? 1 : 2;
                    @endphp
                    <style>
                        .shop-product-grid { 
                            gap: {{ $mobileGapPx }}px; 
                            grid-auto-rows: 1fr;
                        }
                        .shop-product-grid > * { height: 100%; }

                        /*
                          Fallback columns BEFORE Alpine applies the Tailwind grid-cols-* classes.
                          Once Alpine adds any of .grid-cols-1..4, these rules stop matching and
                          the Tailwind classes control the layout normally.
                        */
                        .shop-product-grid:not(.grid-cols-1):not(.grid-cols-2):not(.grid-cols-3):not(.grid-cols-4) {
                            grid-template-columns: repeat({{ $initialMobileCols }}, minmax(0, 1fr));
                        }
                        @media (min-width: 640px) {
                            .shop-product-grid { 
                                gap: 1rem;
                            }
                            .shop-product-grid:not(.grid-cols-1):not(.grid-cols-2):not(.grid-cols-3):not(.grid-cols-4) {
                                grid-template-columns: repeat(2, minmax(0, 1fr));
                            }
                        }
                        @media (min-width: 768px) {
                            .shop-product-grid:not(.grid-cols-1):not(.grid-cols-2):not(.grid-cols-3):not(.grid-cols-4) {
                                grid-template-columns: repeat({{ $initialGridCols === '4' ? 3 : 2 }}, minmax(0, 1fr));
                            }
                        }
                        @media (min-width: 1024px) {
                            .shop-product-grid { 
                                gap: 1.5rem;
                            }
                            .shop-product-grid:not(.grid-cols-1):not(.grid-cols-2):not(.grid-cols-3):not(.grid-cols-4) {
                                grid-template-columns: repeat({{ $initialGridCols }}, minmax(0, 1fr));
                            }
                        }
                    </style>
                    <!-- Grid View -->
                    <div x-show="viewMode === 'grid'" 
                         class="grid shop-product-grid"
                         :class="{
                            '{{ $mobileColClass }} sm:grid-cols-2': gridCols === '2',
                            '{{ $mobileColClass }} sm:grid-cols-2 lg:grid-cols-3': gridCols === '3',
                            '{{ $mobileColClass }} sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4': gridCols === '4'
                         }">
                        @foreach($products as $index => $product)
                            <div class="product-grid-enter h-full" style="animation-delay: {{ $index * 50 }}ms">
                                @include('components.product-card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- List View -->
                    <div x-show="viewMode === 'list'" x-cloak class="space-y-4">
                        @foreach($products as $product)
                            @include('components.product-card-list', ['product' => $product])
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $products->links() }}
                    </div>
                @else
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-search text-4xl text-gray-300"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('messages.no_products_found') }}</h3>
                        <p class="text-gray-500 mb-6 max-w-md mx-auto">{{ __('messages.hero_subtitle') }}</p>
                        <a href="{{ route('shop.index') }}" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition font-medium shadow-lg shadow-primary-600/30">
                            <i class="fas fa-redo mr-2"></i>
                            {{ __('messages.clear_filters') }}
                        </a>
                    </div>
                @endif
