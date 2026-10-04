{{-- Vendor store page: dark hero with the banner image, logo, name, short description, facts and social links. Replace at themes/<slug>/shop/vendor-page/banner.blade.php --}}
<section class="relative bg-gradient-to-r from-gray-900 to-gray-800 overflow-hidden">
    @if($vendor->banner)
        <img src="{{ asset('storage/' . $vendor->banner) }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30">
    @endif
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16 relative">
        <div class="flex flex-col md:flex-row items-center gap-6 md:gap-8">
            @include('shop.vendor-page.logo', ['size' => 'w-28 h-28 md:w-32 md:h-32'])
            <div class="text-center md:text-left text-white flex-1 min-w-0">
                <h1 class="text-3xl md:text-4xl font-bold mb-2">{{ $vendor->store_name }}</h1>
                @if($vendor->description)
                    <p class="text-gray-300 mb-4 max-w-2xl">{{ \Illuminate\Support\Str::limit($vendor->description, 200) }}</p>
                @endif
                @include('shop.vendor-page.meta', ['class' => 'justify-center md:justify-start text-gray-200'])
            </div>
            @include('shop.vendor-page.social')
        </div>
    </div>
</section>
