{{-- Shop page: Chips for the active filters with "clear all". Shared by every theme; replace at themes/<slug>/shop/listing/active-filters.blade.php --}}
        <!-- Active Filters Display -->
        @if(request('category') || request('min_price') || request('max_price') || request('search'))
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-sm text-gray-500 mr-2">{{ __('messages.filters') }}:</span>
            @if(request('search'))
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 text-primary-700 rounded-full text-sm font-medium">
                    <i class="fas fa-search text-xs"></i>
                    "{{ request('search') }}"
                    <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="hover:text-primary-900"><i class="fas fa-times text-xs"></i></a>
                </span>
            @endif
            @if(request('category'))
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 text-primary-700 rounded-full text-sm font-medium">
                    <i class="fas fa-folder text-xs"></i>
                    {{ $categories->firstWhere('slug', request('category'))?->translated_name }}
                    <a href="{{ request()->fullUrlWithQuery(['category' => null]) }}" class="hover:text-primary-900"><i class="fas fa-times text-xs"></i></a>
                </span>
            @endif
            @if(request('min_price') || request('max_price'))
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 text-primary-700 rounded-full text-sm font-medium">
                    <i class="fas fa-tag text-xs"></i>
                    {{ request('min_price') ? format_currency(request('min_price')) : '0' }} - {{ request('max_price') ? format_currency(request('max_price')) : '∞' }}
                    <a href="{{ request()->fullUrlWithQuery(['min_price' => null, 'max_price' => null]) }}" class="hover:text-primary-900"><i class="fas fa-times text-xs"></i></a>
                </span>
            @endif
            <a href="{{ route('shop.index') }}" class="text-sm text-gray-500 hover:text-primary-600 ml-2 underline">{{ __('messages.clear_filters') }}</a>
        </div>
        @endif
