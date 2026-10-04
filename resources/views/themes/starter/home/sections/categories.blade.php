{{-- Starter theme: categories as a simple list of square thumbnails with underlined labels. Same homepage settings. --}}
@if(($appSettings['homepage']['show_categories'] ?? $appSettings['theme']['show_categories'] ?? true) && $categories->count() > 0)
    @php
        $categoriesTitle = $appSettings['homepage']['categories_title'] ?? __('messages.shop_by_category');
        $categoriesBgColor = (string) ($appSettings['homepage']['categories_bg_color'] ?? '');
    @endphp
    <section class="py-10 md:py-12 homepage-body"
        style="{{ $categoriesBgColor ? 'background-color: ' . $categoriesBgColor . ';' : '' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-900 homepage-heading pb-3 mb-6 border-b-2 border-primary-600 inline-block">{{ $categoriesTitle }}</h2>

            <div id="categoriesContainer" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-4">
                @foreach($categories as $category)
                    <a href="{{ route('shop.category', $category->slug) }}" class="group block text-center">
                        <div class="aspect-square overflow-hidden border border-gray-300 group-hover:border-primary-600 transition-colors bg-gray-50">
                            @if($category->image)
                                <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->translated_name }}" loading="lazy" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center"><i class="fas fa-folder text-2xl text-gray-300"></i></div>
                            @endif
                        </div>
                        <h3 class="mt-2 text-sm font-medium text-gray-800 group-hover:text-primary-600 group-hover:underline truncate">{{ $category->translated_name }}</h3>
                        @if($category->products_count ?? false)
                            <p class="text-xs text-gray-400">{{ $category->products_count }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
