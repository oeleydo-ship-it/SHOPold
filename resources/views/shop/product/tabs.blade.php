{{-- Product page: Description / features tabs. Shared by every theme; a theme can replace it at themes/<slug>/shop/product/tabs.blade.php --}}
        <!-- Product Tabs (Description & Features) -->
        <div class="border-t" x-data="{ activeTab: 'description' }">
            <!-- Tab Navigation -->
            <div class="flex border-b bg-gray-50">
                <button type="button" 
                        @click="activeTab = 'description'"
                        :class="activeTab === 'description' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-100'"
                        class="px-6 py-4 font-medium transition-all duration-200 focus:outline-none">
                    <i class="fas fa-file-alt mr-2"></i>{{ __('messages.description') }}
                </button>
                @if($product->features && count($product->features) > 0)
                <button type="button" 
                        @click="activeTab = 'features'"
                        :class="activeTab === 'features' ? 'border-b-2 border-primary-600 text-primary-600 bg-white' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-100'"
                        class="px-6 py-4 font-medium transition-all duration-200 focus:outline-none">
                    <i class="fas fa-list-check mr-2"></i>{{ __('messages.features') ?? 'Features' }}
                </button>
                @endif
            </div>
            
            <!-- Tab Content -->
            <div class="p-6 lg:p-8">
                <!-- Description Tab Content -->
                <div x-show="activeTab === 'description'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="prose max-w-none text-gray-600 product-long-desc leading-relaxed">
                        {!! safe_html(clean_html($product->translated_description)) !!}
                    </div>
                </div>
                
                <!-- Features Tab Content -->
                @if($product->features && count($product->features) > 0)
                <div x-show="activeTab === 'features'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                    <div class="overflow-hidden">
                        <table class="w-full">
                            <tbody class="divide-y divide-gray-200">
                                @foreach($product->features as $feature)
                                <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 w-1/3">
                                        {{ $feature['name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $feature['value'] }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
