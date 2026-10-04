{{-- Category page: category name and description banner. Shared by every theme; replace at themes/<slug>/shop/category-page/banner.blade.php --}}
    <div class="bg-gradient-to-r from-primary-600 to-primary-800 rounded-2xl p-8 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-2">{{ $category->translated_name }}</h1>
        @if($category->translated_description)
            <p class="text-white/80">{{ $category->translated_description }}</p>
        @endif
    </div>
