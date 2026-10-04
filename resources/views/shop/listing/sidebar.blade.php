{{-- Shop page: Desktop filter sidebar (categories, price range). Shared by every theme; replace at themes/<slug>/shop/listing/sidebar.blade.php --}}
            <!-- Sidebar Filters (Desktop) -->
            <aside class="hidden lg:block lg:w-72 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-24">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                            <i class="fas fa-filter text-primary-600"></i>
                            {{ __('messages.filters') }}
                        </h3>
                        @if(request('category') || request('min_price') || request('max_price'))
                        <a href="{{ route('shop.index', request()->only('search')) }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                            {{ __('messages.clear_filters') }}
                        </a>
                        @endif
                    </div>
                    
                    <form action="{{ route('shop.index') }}" method="GET" id="filterForm">
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif

                        <!-- Categories -->
                        <div class="mb-6">
                            <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <i class="fas fa-th-large text-gray-400"></i>
                                {{ __('messages.categories') }}
                            </h4>
                            <div class="space-y-1 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                                <label class="flex items-center p-2.5 rounded-xl cursor-pointer transition-all hover:bg-gray-50 {{ !request('category') ? 'bg-primary-50 border border-primary-100' : '' }}">
                                    <input type="radio" name="category" value="" {{ !request('category') ? 'checked' : '' }} 
                                           class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-gray-300" onchange="document.getElementById('filterForm').submit()">
                                    <span class="ml-3 text-gray-700 font-medium">{{ __('messages.all_categories') }}</span>
                                </label>
                                @foreach($categories as $category)
                                <label class="flex items-center justify-between p-2.5 rounded-xl cursor-pointer transition-all hover:bg-gray-50 {{ request('category') == $category->slug ? 'bg-primary-50 border border-primary-100' : '' }}">
                                    <div class="flex items-center">
                                        <input type="radio" name="category" value="{{ $category->slug }}" {{ request('category') == $category->slug ? 'checked' : '' }} 
                                               class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-gray-300" onchange="document.getElementById('filterForm').submit()">
                                        <span class="ml-3 text-gray-700">{{ $category->translated_name }}</span>
                                    </div>
                                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $category->products_count }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="border-t border-gray-100 my-5"></div>

                        <!-- Price Range -->
                        <div class="mb-6">
                            <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <i class="fas fa-tag text-gray-400"></i>
                                {{ __('messages.price_range') }}
                            </h4>
                            <div class="flex items-center gap-3">
                                <div class="flex-1">
                                    <label class="text-xs text-gray-500 mb-1.5 block">Min</label>
                                    <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="0" 
                                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                                <span class="text-gray-300 mt-5">—</span>
                                <div class="flex-1">
                                    <label class="text-xs text-gray-500 mb-1.5 block">Max</label>
                                    <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Any" 
                                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition font-medium shadow-lg shadow-primary-600/30">
                            <i class="fas fa-check mr-2"></i>{{ __('messages.apply_filters') }}
                        </button>
                    </form>
                </div>
            </aside>
