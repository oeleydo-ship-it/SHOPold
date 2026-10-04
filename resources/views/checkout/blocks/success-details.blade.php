{{-- Order confirmation: order number, total, payment method and destination. Shared by every theme. --}}
        <div class="bg-gray-50 rounded-xl p-6 mb-8">
            <div class="grid md:grid-cols-2 gap-4 text-left">
                <div>
                    <p class="text-sm text-gray-500">Order Number</p>
                    <p class="font-semibold text-lg">{{ $order->order_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Amount</p>
                    <p class="font-semibold text-lg">{{ format_currency($order->total) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Payment Method</p>
                    <p class="font-semibold">{{ $order->payment_method == 'cod' ? 'Cash on Delivery' : ucwords(str_replace('_', ' ', (string) $order->payment_method)) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Shipping To</p>
                    <p class="font-semibold">{{ $order->shipping_city }}, {{ $order->shipping_country }}</p>
                </div>
            </div>
        </div>
