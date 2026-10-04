{{-- Default theme override: starts as a copy of the shared view. Edit this file to change the default theme only. --}}
@if(($appSettings['homepage']['show_categories'] ?? $appSettings['theme']['show_categories'] ?? true) && $categories->count() > 0)
    @php
        $categoriesLabel = $appSettings['homepage']['categories_label'] ?? __('messages.browse');
        $categoriesTitle = $appSettings['homepage']['categories_title'] ?? __('messages.shop_by_category');
        $categoriesBgColor = (string) ($appSettings['homepage']['categories_bg_color'] ?? '');
        $categoriesItemFontSize = (int) ($appSettings['homepage']['categories_item_font_size'] ?? 12);
        $categoriesItemFontSizeMd = (int) ($appSettings['homepage']['categories_item_font_size_md'] ?? 14);
        $categoriesItemFontWeight = (string) ($appSettings['homepage']['categories_item_font_weight'] ?? '500');
        $categoriesItemTextColor = (string) ($appSettings['homepage']['categories_item_text_color'] ?? '#1f2937');
        $categoriesItemHoverColor = (string) ($appSettings['homepage']['categories_item_hover_color'] ?? '#2563eb');
    @endphp
    <style>
        .homepage-category-label { font-size: {{ max(8, min(30, $categoriesItemFontSize)) }}px; font-weight: {{ preg_replace('/[^0-9]/', '', $categoriesItemFontWeight) ?: '500' }}; color: {{ $categoriesItemTextColor }}; }
        @media (min-width: 768px) { .homepage-category-label { font-size: {{ max(8, min(36, $categoriesItemFontSizeMd)) }}px; } }
        .group:hover .homepage-category-label { color: {{ $categoriesItemHoverColor }}; }
    </style>
    <section class="py-12 md:py-16 homepage-body"
        style="{{ $categoriesBgColor ? 'background-color: ' . $categoriesBgColor . ';' : '' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Section Header -->
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8 md:mb-10">
                <div>
                    <span
                        class="inline-flex items-center px-3 py-1.5 bg-primary-600 text-white text-xs font-semibold uppercase tracking-wider rounded-full mb-3">
                        <i class="fas fa-th-large mr-1.5"></i>{{ $categoriesLabel }}
                    </span>
                    <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 homepage-heading">
                        {{ $categoriesTitle }}</h2>
                </div>
            </div>

            <!-- Categories Grid - Show All -->
            <div id="categoriesContainer"
                class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 gap-4 md:gap-6">
                @foreach($categories as $category)
                    <a href="{{ route('shop.category', $category->slug) }}" class="group text-center">
                        <div class="relative w-[4.8rem] h-[4.8rem] md:w-[6rem] md:h-[6rem] mx-auto mb-2">
                            <!-- Smaller circle with clean white background -->
                            <div
                                class="w-full h-full rounded-full bg-white border-2 border-gray-100 overflow-hidden transition-all duration-300 ease-out group-hover:shadow-lg group-hover:scale-105 group-hover:border-primary-400">
                                @if($category->image)
                                    <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->translated_name }}"
                                        class="w-full h-full object-cover transition-transform duration-300 ease-out group-hover:scale-110">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-primary-50">
                                        <i
                                            class="fas fa-folder text-xl md:text-2xl text-primary-300 transition-transform duration-300 group-hover:scale-110 group-hover:text-primary-400"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <h3
                            class="homepage-category-label transition-colors duration-200 max-w-[96px] md:max-w-[120px] mx-auto leading-tight truncate">
                            {{ $category->translated_name }}</h3>
                        @if($category->products_count ?? false)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $category->products_count }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif