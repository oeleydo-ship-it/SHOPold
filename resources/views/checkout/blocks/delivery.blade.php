{{-- Checkout: delivery method: shipping methods or store pickup, plus the AJAX loading state. Shared by every theme; field names and scripts must stay as they are. Replace at themes/<slug>/checkout/blocks/delivery.blade.php --}}
                <!-- Shipping Method -->
                <div class="bg-white rounded-xl shadow-sm p-6" id="shipping-methods-section" x-data="{ deliveryType: 'shipping', selectedPickupLocation: '' }">
                    <h3 class="text-lg font-bold mb-4">Delivery Method</h3>
                    
                    {{-- Delivery Type Selection (only show if pickup locations exist) --}}
                    @if(isset($pickupLocations) && $pickupLocations->isNotEmpty())
                    <div class="flex gap-4 mb-6">
                        <label class="flex-1 flex items-center p-4 border-2 rounded-lg cursor-pointer transition"
                               :class="deliveryType === 'shipping' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="delivery_type" value="shipping" x-model="deliveryType" class="text-primary-600">
                            <div class="ml-3">
                                <span class="font-medium flex items-center"><i class="fas fa-truck mr-2 text-primary-500"></i>Delivery</span>
                                <span class="text-sm text-gray-500">Ship to your address</span>
                            </div>
                        </label>
                        <label class="flex-1 flex items-center p-4 border-2 rounded-lg cursor-pointer transition"
                               :class="deliveryType === 'pickup' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="radio" name="delivery_type" value="pickup" x-model="deliveryType" class="text-primary-600">
                            <div class="ml-3">
                                <span class="font-medium flex items-center"><i class="fas fa-store mr-2 text-primary-500"></i>Store Pickup</span>
                                <span class="text-sm text-gray-500">Pick up from store</span>
                            </div>
                        </label>
                    </div>
                    @else
                    <input type="hidden" name="delivery_type" value="shipping">
                    @endif
                    
                    {{-- Shipping Methods (shown when delivery type is shipping) --}}
                    <div id="shipping-methods-container" x-show="deliveryType === 'shipping'" x-transition>
                        @if(isset($shippingMethods) && $shippingMethods->isNotEmpty())
                            <div class="space-y-3">
                                @foreach($shippingMethods as $method)
                                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-primary-500 transition {{ $loop->first ? 'border-primary-500 bg-primary-50' : '' }}">
                                        <input type="radio" name="shipping_method" value="{{ $method->id }}" 
                                               data-cost="{{ $method->calculateCost($subtotal) }}"
                                               {{ $loop->first ? 'checked' : '' }} 
                                               class="text-primary-600 shipping-method-radio">
                                        <div class="ml-3 flex-1">
                                            <div class="flex items-center justify-between">
                                                <span class="font-medium">{{ $method->name }}</span>
                                                <span class="font-semibold text-primary-600">{{ format_currency($method->calculateCost($subtotal)) }}</span>
                                            </div>
                                            @if($method->description)
                                                <span class="block text-sm text-gray-500">{{ $method->description }}</span>
                                            @endif
                                            @if($method->delivery_time)
                                                <span class="block text-xs text-gray-400 mt-1"><i class="fas fa-clock mr-1"></i>{{ $method->delivery_time }}</span>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <p class="text-yellow-800"><i class="fas fa-exclamation-triangle mr-2"></i>No shipping methods available for your location. Please contact support.</p>
                            </div>
                            <input type="hidden" name="shipping_method" value="">
                        @endif
                    </div>
                    
                    {{-- Pickup Locations (shown when delivery type is pickup) --}}
                    @if(isset($pickupLocations) && $pickupLocations->isNotEmpty())
                    <div x-show="deliveryType === 'pickup'" x-transition class="space-y-3">
                        <p class="text-sm text-gray-600 mb-3"><i class="fas fa-info-circle mr-1"></i>Select a store location to pick up your order. <span class="text-green-600 font-medium">Free pickup!</span></p>
                        @foreach($pickupLocations as $location)
                            <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:border-primary-500 transition"
                                   :class="selectedPickupLocation == '{{ $location->id }}' ? 'border-primary-500 bg-primary-50' : ''">
                                <input type="radio" name="pickup_location_id" value="{{ $location->id }}" 
                                       x-model="selectedPickupLocation"
                                       data-cost="0"
                                       class="text-primary-600 mt-1">
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium">{{ $location->name }}</span>
                                        <span class="font-semibold text-green-600">FREE</span>
                                    </div>
                                    <span class="block text-sm text-gray-500">
                                        <i class="fas fa-map-marker-alt mr-1"></i>{{ $location->address }}, {{ $location->city }}@if($location->state), {{ $location->state }}@endif, {{ $location->country }}
                                    </span>
                                    @if($location->phone)
                                        <span class="block text-xs text-gray-400 mt-1"><i class="fas fa-phone mr-1"></i>{{ $location->phone }}</span>
                                    @endif
                                    @if($location->instructions)
                                        <span class="block text-xs text-blue-600 mt-1"><i class="fas fa-info-circle mr-1"></i>{{ $location->instructions }}</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @endif
                    
                    <div id="shipping-loading" class="hidden py-4 text-center text-gray-500">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Loading shipping options...
                    </div>
                </div>
