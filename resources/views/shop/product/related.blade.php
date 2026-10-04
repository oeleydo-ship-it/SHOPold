{{-- Product page: Related products and AI recommendations. Shared by every theme; a theme can replace it at themes/<slug>/shop/product/related.blade.php --}}
    <!-- Related Products -->
    @if($relatedProducts->count() > 0)
    <div class="mt-12">
        <h3 class="text-2xl font-bold mb-6">{{ __('messages.related_products') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6" style="grid-auto-rows: 1fr;">
            @foreach($relatedProducts as $related)
                <div class="h-full">
                    @include('components.product-card', ['product' => $related])
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- AI-Powered Recommendations -->
    @if(isset($aiRecommendations) && $aiRecommendations->count() > 0)
    <div class="mt-12">
        <div class="flex items-center gap-3 mb-6">
            <h3 class="text-2xl font-bold">Recommended For You</h3>
            <span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full flex items-center gap-1">
                <i class="fas fa-magic"></i> AI Powered
            </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6" style="grid-auto-rows: 1fr;">
            @foreach($aiRecommendations as $recommended)
                <div class="h-full">
                    @include('components.product-card', ['product' => $recommended])
                </div>
            @endforeach
        </div>
    </div>
    @endif
