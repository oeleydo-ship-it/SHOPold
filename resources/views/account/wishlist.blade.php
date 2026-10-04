@extends('layouts.account')

@section('title', 'Wishlist')

@section('account_heading', 'My Wishlist')

@section('account_content')
            @if($wishlist->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-6">
                @foreach($wishlist as $item)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden group">
                    <div class="aspect-square bg-gray-100 relative overflow-hidden">
                        @if($item->product->image)
                            <img src="{{ $item->product->image_url }}" alt="{{ $item->product->translated_name }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center"><i class="fas fa-image text-3xl md:text-4xl text-gray-400"></i></div>
                        @endif
                        <form action="{{ route('account.wishlist.remove') }}" method="POST" class="absolute top-2 right-2 md:top-3 md:right-3">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                            <button type="submit" class="w-7 h-7 md:w-8 md:h-8 bg-white rounded-full flex items-center justify-center text-red-500 hover:bg-red-500 hover:text-white transition shadow">
                                <i class="fas fa-times text-sm"></i>
                            </button>
                        </form>
                    </div>
                    <div class="p-3 md:p-4">
                        <a href="{{ route('shop.show', $item->product->slug) }}" class="font-semibold text-gray-900 hover:text-primary-600 text-sm md:text-base line-clamp-2">{{ $item->product->translated_name }}</a>
                        <p class="text-primary-600 font-bold mt-1 md:mt-2 text-sm md:text-base">{{ format_currency($item->product->current_price) }}</p>
                        <form action="{{ route('cart.add') }}" method="POST" class="mt-2 md:mt-3">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                            <button type="submit" class="w-full py-2 bg-primary-600 text-white text-xs md:text-sm rounded-lg hover:bg-primary-700 transition">
                                <i class="fas fa-shopping-cart mr-1 md:mr-2"></i><span class="hidden sm:inline">Add to </span>Cart
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4 md:mt-6">{{ $wishlist->links() }}</div>
            @else
            <div class="bg-white rounded-xl shadow-sm p-8 md:p-12 text-center">
                <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-heart text-2xl md:text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg md:text-xl font-semibold text-gray-900 mb-2">Your wishlist is empty</h3>
                <p class="text-gray-500 mb-6 text-sm md:text-base">Save items you love to your wishlist</p>
                <a href="{{ route('shop.index') }}" class="inline-flex items-center px-5 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition text-sm md:text-base">
                    <i class="fas fa-shopping-bag mr-2"></i>Browse Products
                </a>
            </div>
            @endif
@endsection
