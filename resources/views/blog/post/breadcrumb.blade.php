{{-- Blog post: breadcrumb. Shared by every theme; replace at themes/<slug>/blog/post/breadcrumb.blade.php --}}
            <!-- Breadcrumb -->
            <nav class="mb-8 text-sm">
                <a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600">Home</a>
                <span class="mx-2 text-gray-400">/</span>
                <a href="{{ route('blog.index') }}" class="text-gray-500 hover:text-primary-600">Blog</a>
                @if($post->category)
                <span class="mx-2 text-gray-400">/</span>
                <a href="{{ route('blog.category', $post->category->slug) }}" class="text-gray-500 hover:text-primary-600">{{ $post->category->name }}</a>
                @endif
                <span class="mx-2 text-gray-400">/</span>
                <span class="text-gray-900">{{ Str::limit($post->title, 30) }}</span>
            </nav>
