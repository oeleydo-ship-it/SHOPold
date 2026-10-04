{{-- Category page: links to the other categories (optional block; themes choose whether to show it). Shared by every theme. --}}
@php($otherCategories = ($categories ?? collect())->where('id', '!=', $category->id)->where('products_count', '>', 0)->take(12))
@if($otherCategories->isNotEmpty())
    <nav class="category-pills flex flex-wrap gap-2 mb-8" aria-label="{{ __('messages.categories') ?? 'Categories' }}">
        @foreach($otherCategories as $other)
            <a href="{{ route('shop.category', $other->slug) }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm bg-white border border-gray-200 rounded-full text-gray-700 hover:border-primary-500 hover:text-primary-600 transition">
                {{ $other->translated_name }}
                <span class="text-xs text-gray-400">{{ $other->products_count }}</span>
            </a>
        @endforeach
    </nav>
@endif
