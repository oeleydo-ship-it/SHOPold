{{-- Category page: breadcrumb trail. Shared by every theme; replace at themes/<slug>/shop/category-page/breadcrumb.blade.php --}}
    <nav class="flex mb-8">
        <ol class="flex items-center space-x-2 text-sm">
            <li><a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600">{{ __('messages.home') }}</a></li>
            <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
            <li><a href="{{ route('shop.index') }}" class="text-gray-500 hover:text-primary-600">{{ __('messages.shop') }}</a></li>
            <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
            <li class="text-gray-900 font-medium">{{ $category->translated_name }}</li>
        </ol>
    </nav>
