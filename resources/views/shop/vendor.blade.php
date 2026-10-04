@extends('layouts.app')

@section('title', $vendor->store_name)

@section('content')
<!-- Vendor Banner -->
<div class="relative bg-gradient-to-r from-gray-900 to-gray-800 overflow-hidden">
    @if($vendor->banner)
    <img src="{{ asset('storage/' . $vendor->banner) }}" alt="{{ $vendor->store_name }}" 
         class="absolute inset-0 w-full h-full object-cover opacity-30">
    @endif
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 relative z-10">
        <div class="flex flex-col md:flex-row items-center gap-8">
            <!-- Logo -->
            <div class="w-32 h-32 bg-white rounded-xl shadow-lg flex items-center justify-center overflow-hidden">
                @if($vendor->logo)
                <img src="{{ asset('storage/' . $vendor->logo) }}" alt="{{ $vendor->store_name }}" class="w-full h-full object-cover">
                @else
                <i class="fas fa-store text-5xl text-gray-400"></i>
                @endif
            </div>
            
            <!-- Info -->
            <div class="text-center md:text-left text-white flex-1">
                <h1 class="text-3xl md:text-4xl font-bold mb-2">{{ $vendor->store_name }}</h1>
                @if($vendor->description)
                <p class="text-gray-300 mb-4 max-w-2xl">{{ Str::limit($vendor->description, 200) }}</p>
                @endif
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 text-sm">
                    @if($vendor->average_rating > 0)
                    <div class="flex items-center">
                        <div class="flex text-yellow-400 mr-1">
                            @for($i = 1; $i <= 5; $i++)
                            <i class="fas {{ $i <= $vendor->average_rating ? 'fa-star' : 'fa-star text-gray-500' }}"></i>
                            @endfor
                        </div>
                        <span>({{ $vendor->rating_count }} reviews)</span>
                    </div>
                    @endif
                    <span class="text-gray-400">|</span>
                    <span><i class="fas fa-box mr-1"></i>{{ $products->total() }} Products</span>
                    @if($vendor->city)
                    <span class="text-gray-400">|</span>
                    <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $vendor->city }}, {{ $vendor->country }}</span>
                    @endif
                </div>
            </div>
            
            <!-- Social Links -->
            @if($vendor->social_links)
            <div class="flex gap-3">
                @if(!empty($vendor->social_links['facebook']))
                <a href="{{ $vendor->social_links['facebook'] }}" target="_blank" 
                   class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center text-white hover:bg-white/20 transition">
                    <i class="fab fa-facebook-f"></i>
                </a>
                @endif
                @if(!empty($vendor->social_links['twitter']))
                <a href="{{ $vendor->social_links['twitter'] }}" target="_blank"
                   class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center text-white hover:bg-white/20 transition">
                    <i class="fab fa-twitter"></i>
                </a>
                @endif
                @if(!empty($vendor->social_links['instagram']))
                <a href="{{ $vendor->social_links['instagram'] }}" target="_blank"
                   class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center text-white hover:bg-white/20 transition">
                    <i class="fab fa-instagram"></i>
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Products -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Products</h2>
        <div class="flex items-center gap-4">
            <span class="text-gray-500">{{ $products->total() }} products</span>
        </div>
    </div>

    @if($products->count() > 0)
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" style="grid-auto-rows: 1fr;">
        @foreach($products as $product)
            <div class="h-full">
                @include('components.product-card', ['product' => $product])
            </div>
        @endforeach
    </div>
    
    <div class="mt-8">
        {{ $products->links() }}
    </div>
    @else
    <div class="text-center py-16">
        <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">This vendor hasn't added any products yet.</p>
    </div>
    @endif
</div>
@endsection



