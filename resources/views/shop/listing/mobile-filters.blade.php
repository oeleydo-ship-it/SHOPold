{{-- Shop page: Mobile filter button and collapsible filter form (uses showFilters from the page state). Shared by every theme; replace at themes/<slug>/shop/listing/mobile-filters.blade.php --}}
        <!-- Mobile Filter Bar -->
        <div class="lg:hidden mb-4">
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <select onchange="if(this.value) window.location.href=this.value" 
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 shadow-sm">
                        <option value="{{ route('shop.index', array_merge(request()->except('category'), [])) }}" {{ !request('category') ? 'selected' : '' }}>
                            {{ __('messages.all_categories') }}
                        </option>
                        @foreach($categories as $category)
                        <option value="{{ route('shop.index', array_merge(request()->except('category'), ['category' => $category->slug])) }}" 
                                {{ request('category') == $category->slug ? 'selected' : '' }}>
                            {{ $category->translated_name }} ({{ $category->products_count }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <button @click="showFilters = !showFilters" 
                        class="flex items-center gap-2 px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-all"
                        :class="{ 'ring-2 ring-primary-500 border-primary-500': showFilters }">
                    <i class="fas fa-sliders-h"></i>
                    <span>{{ __('messages.filters') }}</span>
                    @if(request('min_price') || request('max_price'))
                        <span class="w-2 h-2 bg-primary-600 rounded-full animate-pulse"></span>
                    @endif
                </button>
            </div>

            <!-- Mobile Filter Panel -->
            <div x-show="showFilters" x-collapse x-cloak class="mt-3">
                <form action="{{ route('shop.index') }}" method="GET" class="bg-white rounded-2xl shadow-lg border border-gray-100 p-5">
                    @if(request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif

                    <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="fas fa-tag text-primary-600"></i>
                        {{ __('messages.price_range') }}
                    </h4>
                    <div class="flex items-center gap-3 mb-5">
                        <div class="flex-1">
                            <label class="text-xs text-gray-500 mb-1 block">Min</label>
                            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="0" 
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500">
                        </div>
                        <span class="text-gray-300 mt-5">—</span>
                        <div class="flex-1">
                            <label class="text-xs text-gray-500 mb-1 block">Max</label>
                            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Any" 
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition font-medium shadow-lg shadow-primary-600/30">
                            <i class="fas fa-check mr-2"></i>{{ __('messages.apply_filters') }}
                        </button>
                        <a href="{{ route('shop.index') }}" class="px-5 py-3 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50 transition font-medium">
                            {{ __('messages.cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
