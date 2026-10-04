{{-- Vendor store page: rating, product count and location as a row of facts. Replace at themes/<slug>/shop/vendor-page/meta.blade.php --}}
<div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm {{ $class ?? '' }}">
    @if($vendor->average_rating > 0)
        <span class="inline-flex items-center gap-1">
            <span class="text-yellow-400" aria-hidden="true">@for($i = 1; $i <= 5; $i++)<i class="fas fa-star {{ $i <= round($vendor->average_rating) ? '' : 'opacity-30' }}"></i>@endfor</span>
            <span>{{ number_format($vendor->average_rating, 1) }} ({{ trans_choice(':count review|:count reviews', $vendor->rating_count, ['count' => $vendor->rating_count]) }})</span>
        </span>
    @endif
    <span><i class="fas fa-box mr-1" aria-hidden="true"></i>{{ trans_choice(':count product|:count products', $products->total(), ['count' => $products->total()]) }}</span>
    @if($vendor->city)
        <span><i class="fas fa-map-marker-alt mr-1" aria-hidden="true"></i>{{ collect([$vendor->city, $vendor->country])->filter()->implode(', ') }}</span>
    @endif
</div>
