{{-- Order confirmation: "view order" and "continue shopping" buttons. Shared by every theme. --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('account.orders.show', $order) }}" class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition">
                View Order Details
            </a>
            <a href="{{ route('shop.index') }}" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                Continue Shopping
            </a>
        </div>
