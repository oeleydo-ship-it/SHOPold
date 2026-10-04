{{-- Shop page: Title banner with breadcrumb, result count and category pills. Shared by every theme; replace at themes/<slug>/shop/listing/hero.blade.php --}}
    <!-- Shop Hero Banner -->
    <div class="shop-hero border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <!-- Breadcrumb -->
                    <nav class="flex mb-3" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2 text-sm">
                            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600 transition"><i class="fas fa-home"></i></a></li>
                            <li><i class="fas fa-chevron-right text-gray-300 text-xs"></i></li>
                            <li class="text-gray-900 font-medium">{{ __('messages.shop') }}</li>
                            @if(request('category'))
                                <li><i class="fas fa-chevron-right text-gray-300 text-xs"></i></li>
                                <li class="text-primary-600 font-medium">{{ $categories->firstWhere('slug', request('category'))?->translated_name }}</li>
                            @endif
                        </ol>
                    </nav>
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                        @if(request('search'))
                            {{ __('messages.search') }}: "{{ request('search') }}"
                        @elseif(request('category'))
                            {{ $categories->firstWhere('slug', request('category'))?->translated_name ?? __('messages.shop') }}
                        @else
                            {{ __('messages.shop') }}
                        @endif
                    </h1>
                    @php
                        $showDescriptionDesktop = setting('shop.show_description_desktop', true);
                        $showDescriptionMobile = setting('shop.show_description_mobile', true);
                    @endphp
                    @if($showDescriptionDesktop || $showDescriptionMobile)
                    <p class="text-gray-500 mt-1 {{ !$showDescriptionMobile ? 'hidden md:block' : '' }} {{ !$showDescriptionDesktop ? 'md:hidden' : '' }}">{{ __('messages.shop_description') }}</p>
                    @endif
                </div>
                
                <!-- Quick Category Pills (Desktop) -->
                <div class="hidden lg:flex items-center gap-2 flex-wrap">
                    <a href="{{ route('shop.index', request()->except('category')) }}" 
                       class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 {{ !request('category') ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/30' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200' }}">
                        {{ __('messages.all_categories') }}
                    </a>
                    @foreach($categories->take(5) as $category)
                        <a href="{{ route('shop.index', array_merge(request()->except('category'), ['category' => $category->slug])) }}" 
                           class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 {{ request('category') == $category->slug ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/30' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200' }}">
                            {{ $category->translated_name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
