{{-- Blog list: title and intro (blog or category). Shared by every theme; replace at themes/<slug>/blog/blocks/header.blade.php --}}
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                @if(isset($category))
                    {{ $category->name }}
                @else
                    Our Blog
                @endif
            </h1>
            <p class="text-gray-600 max-w-2xl mx-auto">
                @if(isset($category) && $category->description)
                    {{ $category->description }}
                @else
                    Stay updated with the latest news, tips, and insights
                @endif
            </p>
        </div>
