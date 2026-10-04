{{-- Cart page: order summary card: coupon, totals and the checkout / login buttons. Shared by every theme; replace at themes/<slug>/cart/blocks/summary.blade.php --}}
            <div class="bg-white rounded-xl shadow-sm p-6 sticky top-24">
                <h3 class="text-lg font-bold mb-4">Order Summary</h3>
                
                <!-- Coupon -->
                @if(session('coupon'))
                    <div class="flex items-center justify-between bg-green-50 p-3 rounded-lg mb-4">
                        <span class="text-green-700">
                            <i class="fas fa-tag mr-2"></i>{{ session('coupon')['code'] }}
                        </span>
                        <form action="{{ route('cart.coupon.remove') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                @else
                    <form action="{{ route('cart.coupon') }}" method="POST" class="mb-4">
                        @csrf
                        <div class="flex gap-2">
                            <input type="text" name="coupon_code" placeholder="Coupon code" class="flex-1 px-4 py-2 border rounded-lg text-sm">
                            <button type="submit" class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition text-sm">
                                Apply
                            </button>
                        </div>
                    </form>
                @endif

                <div class="space-y-3 text-gray-600">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-medium text-gray-900">{{ format_currency($cart->total) }}</span>
                    </div>
                    @if(session('coupon'))
                    <div class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>-{{ format_currency(session('coupon')['discount']) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span>Shipping</span>
                        <span class="text-gray-900">Calculated at checkout</span>
                    </div>
                    <hr>
                    <div class="flex justify-between text-lg font-bold text-gray-900">
                        <span>Total</span>
                        <span>{{ format_currency($cart->total - (session('coupon')['discount'] ?? 0)) }}</span>
                    </div>
                </div>

                @auth
                    <a href="{{ route('checkout.index') }}" class="block w-full mt-6 py-3 bg-primary-600 text-white text-center font-semibold rounded-lg hover:bg-primary-700 transition">
                        Proceed to Checkout
                    </a>
                @else
                    @if(setting('features.guest_checkout', false))
                        <a href="{{ route('checkout.index') }}" class="block w-full mt-6 py-3 bg-primary-600 text-white text-center font-semibold rounded-lg hover:bg-primary-700 transition">
                            Proceed to Checkout
                        </a>
                        <p class="text-sm text-gray-500 text-center mt-2">
                            Checkout as guest or <a href="{{ route('login') }}" class="text-primary-600 hover:underline">Login</a>
                        </p>
                    @else
                        <a href="{{ route('login') }}" class="block w-full mt-6 py-3 bg-primary-600 text-white text-center font-semibold rounded-lg hover:bg-primary-700 transition">
                            Login to Checkout
                        </a>
                        <p class="text-sm text-gray-500 text-center mt-2">
                            Don't have an account? <a href="{{ route('register') }}" class="text-primary-600 hover:underline">Register</a>
                        </p>
                    @endif
                @endauth
            </div>
