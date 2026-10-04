{{-- Vendor store page: the store logo, or a store icon. $size = Tailwind size classes. Replace at themes/<slug>/shop/vendor-page/logo.blade.php --}}
<div class="{{ $size ?? 'w-28 h-28' }} shrink-0 bg-white rounded-xl shadow-lg flex items-center justify-center overflow-hidden">
    @if($vendor->logo)
        <img src="{{ asset('storage/' . $vendor->logo) }}" alt="{{ $vendor->store_name }}" class="w-full h-full object-cover">
    @else
        <i class="fas fa-store text-4xl text-gray-400" aria-hidden="true"></i>
    @endif
</div>
