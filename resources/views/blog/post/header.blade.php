{{-- Blog post: category badge, title, excerpt, author/date/views. Shared by every theme; replace at themes/<slug>/blog/post/header.blade.php --}}
            <!-- Header -->
            <header class="mb-8">
                @if($post->category)
                <a href="{{ route('blog.category', $post->category->slug) }}" 
                   class="inline-block px-3 py-1 bg-primary-100 text-primary-600 text-sm font-semibold rounded-full mb-4 hover:bg-primary-200">
                    {{ $post->category->name }}
                </a>
                @endif
                
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">{{ $post->title }}</h1>
                
                <div class="flex items-center gap-6 text-gray-500">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $post->author->name ?? 'Admin' }}</p>
                            <p class="text-sm">Author</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-calendar mr-2"></i>
                        <span>{{ $post->published_at->format('F d, Y') }}</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-eye mr-2"></i>
                        <span>{{ number_format($post->views) }} views</span>
                    </div>
                </div>
            </header>
