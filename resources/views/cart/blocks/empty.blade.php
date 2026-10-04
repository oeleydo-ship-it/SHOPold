{{-- Cart page: empty cart message. Shared by every theme; replace at themes/<slug>/cart/blocks/empty.blade.php --}}
    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
        <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Your cart is empty</h3>
        <p class="text-gray-600 mb-6">Looks like you haven't added any items yet</p>
        <a href="{{ route('shop.index') }}" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
            <i class="fas fa-shopping-bag mr-2"></i>Start Shopping
        </a>
    </div>
