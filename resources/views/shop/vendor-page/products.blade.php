{{-- Vendor store page: product grid, pagination and the empty state. Replace at themes/<slug>/shop/vendor-page/products.blade.php --}}
@if($products->count() > 0)
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6" style="grid-auto-rows: 1fr;">
        @foreach($products as $product)
            <div class="h-full">
                @include('components.product-card', ['product' => $product])
            </div>
        @endforeach
    </div>
    <div class="mt-8">{{ $products->links() }}</div>
@else
    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
        <i class="fas fa-box-open text-6xl text-gray-300 mb-4" aria-hidden="true"></i>
        <p class="text-gray-500 text-lg">{{ $vendor->store_name }} hasn't added any products yet.</p>
        <a href="{{ route('shop.index') }}" class="inline-flex items-center mt-6 px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">Browse the shop</a>
    </div>
@endif
