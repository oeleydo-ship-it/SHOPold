{{-- Modern theme: categories as image tiles with the name over a gradient. Uses the same homepage settings. --}}
@if(($appSettings['homepage']['show_categories'] ?? $appSettings['theme']['show_categories'] ?? true) && $categories->count() > 0)
    @php
        $categoriesLabel = $appSettings['homepage']['categories_label'] ?? __('messages.browse');
        $categoriesTitle = $appSettings['homepage']['categories_title'] ?? __('messages.shop_by_category');
        $categoriesBgColor = (string) ($appSettings['homepage']['categories_bg_color'] ?? '');
    @endphp
    <section class="py-12 md:py-16 homepage-body"
        style="{{ $categoriesBgColor ? 'background-color: ' . $categoriesBgColor . ';' : '' }}">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8 md:mb-10 text-center">
                <span class="inline-flex items-center px-3 py-1.5 bg-primary-100 text-primary-700 text-xs font-bold uppercase tracking-widest rounded-full mb-3">
                    <i class="fas fa-th-large mr-1.5"></i>{{ $categoriesLabel }}
                </span>
                <h2 class="text-2xl md:text-3xl lg:text-4xl font-extrabold text-gray-900 homepage-heading">{{ $categoriesTitle }}</h2>
            </div>

            <div id="categoriesContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-5">
                @foreach($categories as $category)
                    <a href="{{ route('shop.category', $category->slug) }}"
                        class="group relative block aspect-square overflow-hidden rounded-3xl bg-gradient-to-br from-primary-100 to-primary-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        @if($category->image)
                            <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->translated_name }}" loading="lazy"
                                class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center">
                                <i class="fas fa-folder text-4xl text-primary-400"></i>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
                        <div class="absolute inset-x-0 bottom-0 p-3 md:p-4">
                            <h3 class="text-white font-bold text-sm md:text-base leading-tight truncate">{{ $category->translated_name }}</h3>
                            @if($category->products_count ?? false)
                                <p class="text-white/80 text-xs mt-0.5">{{ $category->products_count }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif
