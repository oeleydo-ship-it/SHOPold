{{-- Cart page: continue shopping and clear cart links. Shared by every theme; replace at themes/<slug>/cart/blocks/actions.blade.php --}}
            <div class="flex justify-between mt-4">
                <a href="{{ route('shop.index') }}" class="text-primary-600 hover:text-primary-700">
                    <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                </a>
                <form action="{{ route('cart.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash mr-2"></i>Clear Cart
                    </button>
                </form>
            </div>
