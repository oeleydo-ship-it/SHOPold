{{-- Checkout: order summary card with totals and the Place Order button. Shared by every theme; field names and scripts must stay as they are. Replace at themes/<slug>/checkout/blocks/summary.blade.php --}}
                <div class="bg-white rounded-xl shadow-sm p-6 sticky top-24">
                    <h3 class="text-lg font-bold mb-4">Order Summary</h3>
                    
                    <div class="divide-y mb-4">
                        @foreach($cart->items as $item)
                        <div class="py-3 flex gap-3">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                @if($item->product->image)
                                    <img src="{{ $item->product->image_url }}" alt="{{ $item->product->translated_name }}" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-sm">{{ $item->product->translated_name }}</p>
                                <p class="text-sm text-gray-500">Qty: {{ $item->quantity }}</p>
                            </div>
                            <p class="font-medium">{{ format_currency($item->subtotal) }}</p>
                        </div>
                        @endforeach
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span>Subtotal</span><span>{{ format_currency($subtotal) }}</span></div>
                        @if($discount > 0)
                        <div class="flex justify-between text-green-600"><span>Discount</span><span>-{{ format_currency($discount) }}</span></div>
                        @endif
                        <div class="flex justify-between"><span>Shipping</span><span id="shipping-cost">{{ format_currency($shipping) }}</span></div>
                        <div class="flex justify-between"><span>Tax</span><span id="tax-amount">{{ format_currency($tax) }}</span></div>
                        <hr>
                        <div class="flex justify-between text-lg font-bold"><span>Total</span><span id="order-total">{{ format_currency($total) }}</span></div>
                    </div>

                    <button type="submit" class="w-full mt-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition">
                        Place Order
                    </button>
                </div>
