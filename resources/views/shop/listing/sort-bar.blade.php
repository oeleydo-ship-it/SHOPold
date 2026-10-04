{{-- Shop page: Result count, grid/list toggle, column picker and sort select (uses viewMode/gridCols from the page state). Shared by every theme; replace at themes/<slug>/shop/listing/sort-bar.blade.php --}}
                <!-- Sort Bar -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-gray-600 text-sm">
                            {{ __('messages.showing') }} 
                            <span class="font-semibold text-gray-900">{{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }}</span> 
                            {{ __('messages.of') }} 
                            <span class="font-semibold text-gray-900">{{ $products->total() }}</span> 
                            {{ __('messages.products') }}
                        </p>
                        <div class="flex items-center gap-3 flex-wrap">
                            @if(setting('shop.show_per_page', true))
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-500">{{ __('messages.showing') }}:</label>
                                <select onchange="window.location.href=this.value" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 12]) }}" {{ ($perPage ?? 12) == 12 ? 'selected' : '' }}>12</option>
                                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 24]) }}" {{ ($perPage ?? 12) == 24 ? 'selected' : '' }}>24</option>
                                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 36]) }}" {{ ($perPage ?? 12) == 36 ? 'selected' : '' }}>36</option>
                                    <option value="{{ request()->fullUrlWithQuery(['per_page' => 48]) }}" {{ ($perPage ?? 12) == 48 ? 'selected' : '' }}>48</option>
                                </select>
                            </div>
                            @endif
                            
                            @if(setting('shop.show_grid_options', true))
                            <!-- Grid Column Options -->
                            <div class="hidden md:flex items-center gap-1 bg-gray-100 rounded-xl p-1">
                                <button @click="setGridCols('2'); setViewMode('grid')" 
                                        :class="viewMode === 'grid' && gridCols === '2' ? 'bg-white shadow-sm text-primary-600' : 'text-gray-400 hover:text-gray-600'"
                                        class="p-2 rounded-lg transition-all" title="2 Columns">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                                        <rect x="1" y="1" width="6" height="14" rx="1"/>
                                        <rect x="9" y="1" width="6" height="14" rx="1"/>
                                    </svg>
                                </button>
                                <button @click="setGridCols('3'); setViewMode('grid')" 
                                        :class="viewMode === 'grid' && gridCols === '3' ? 'bg-white shadow-sm text-primary-600' : 'text-gray-400 hover:text-gray-600'"
                                        class="p-2 rounded-lg transition-all" title="3 Columns">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                                        <rect x="1" y="1" width="4" height="14" rx="0.5"/>
                                        <rect x="6" y="1" width="4" height="14" rx="0.5"/>
                                        <rect x="11" y="1" width="4" height="14" rx="0.5"/>
                                    </svg>
                                </button>
                                <button @click="setGridCols('4'); setViewMode('grid')" 
                                        :class="viewMode === 'grid' && gridCols === '4' ? 'bg-white shadow-sm text-primary-600' : 'text-gray-400 hover:text-gray-600'"
                                        class="p-2 rounded-lg transition-all" title="4 Columns">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                                        <rect x="1" y="1" width="3" height="14" rx="0.5"/>
                                        <rect x="5" y="1" width="3" height="14" rx="0.5"/>
                                        <rect x="9" y="1" width="3" height="14" rx="0.5"/>
                                        <rect x="13" y="1" width="2" height="14" rx="0.5"/>
                                    </svg>
                                </button>
                                <div class="w-px h-4 bg-gray-300 mx-1"></div>
                                <button @click="setViewMode('list')" 
                                        :class="viewMode === 'list' ? 'bg-white shadow-sm text-primary-600' : 'text-gray-400 hover:text-gray-600'"
                                        class="p-2 rounded-lg transition-all" title="List View">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                                        <rect x="1" y="1" width="14" height="3" rx="0.5"/>
                                        <rect x="1" y="6" width="14" height="3" rx="0.5"/>
                                        <rect x="1" y="11" width="14" height="3" rx="0.5"/>
                                    </svg>
                                </button>
                            </div>
                            @endif
                            
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-500">{{ __('messages.sort_by') }}:</label>
                                <select onchange="window.location.href=this.value" class="px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white min-w-[140px]">
                                    <option value="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>{{ __('messages.newest') }}</option>
                                    <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_low']) }}" {{ request('sort') == 'price_low' ? 'selected' : '' }}>{{ __('messages.price_low_high') }}</option>
                                    <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_high']) }}" {{ request('sort') == 'price_high' ? 'selected' : '' }}>{{ __('messages.price_high_low') }}</option>
                                    <option value="{{ request()->fullUrlWithQuery(['sort' => 'name']) }}" {{ request('sort') == 'name' ? 'selected' : '' }}>{{ __('messages.name') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
