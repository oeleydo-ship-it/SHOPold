{{-- Category page: product grid, pagination and the empty state. Shared by every theme; replace at themes/<slug>/shop/category-page/products.blade.php --}}
    @if($products->count() > 0)
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" style="grid-auto-rows: 1fr;">
            @foreach($products as $product)
                <div class="h-full">
                    @include('components.product-card', ['product' => $product])
                </div>
            @endforeach
        </div>
        <div class="mt-8">{{ $products->links() }}</div>
    @else
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ __('messages.no_products_found') }}</h3>
            <p class="text-gray-600 mb-6">{{ __('messages.check_back_later') ?? 'Check back later for new products' }}</p>
            <a href="{{ route('shop.index') }}" class="inline-flex items-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                {{ __('messages.view_all') }}
            </a>
        </div>
    @endif
