@php
    $showBlog = setting('homepage.show_blog', false);
    $blogTitle = setting('homepage.blog_title', 'Latest Blog Posts');
    $blogSubtitle = setting('homepage.blog_subtitle', 'Stay updated with our latest news and articles');
    $blogCount = (int) setting('homepage.blog_count', 3);
    $blogColumns = (int) setting('homepage.blog_columns', 3);
@endphp

@if($showBlog && isset($blogPosts) && $blogPosts->count() > 0)
<section class="py-12 md:py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <!-- Section Header -->
        <div class="text-center mb-10">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 homepage-heading">{{ $blogTitle }}</h2>
            @if($blogSubtitle)
                <p class="text-gray-600 mt-2 max-w-2xl mx-auto">{{ $blogSubtitle }}</p>
            @endif
        </div>

        <!-- Blog Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ $blogColumns }} gap-6 md:gap-8">
            @foreach($blogPosts as $post)
            <article class="bg-white rounded-xl shadow-sm overflow-hidden group hover:shadow-lg transition-all duration-300">
                <!-- Featured Image -->
                <a href="{{ route('blog.show', $post->slug) }}" class="block aspect-[16/10] overflow-hidden">
                    @if($post->image_url)
                        <img src="{{ $post->image_url }}" 
                             alt="{{ $post->title }}" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-primary-100 to-primary-200 flex items-center justify-center">
                            <i class="fas fa-newspaper text-4xl text-primary-400"></i>
                        </div>
                    @endif
                </a>

                <!-- Content -->
                <div class="p-5 md:p-6">
                    <!-- Category & Date -->
                    <div class="flex items-center gap-3 text-xs text-gray-500 mb-3">
                        @if($post->category)
                            <a href="{{ route('blog.category', $post->category->slug) }}" 
                               class="bg-primary-100 text-primary-700 px-2 py-1 rounded-full hover:bg-primary-200 transition">
                                {{ $post->category->name }}
                            </a>
                        @endif
                        <span class="flex items-center gap-1">
                            <i class="far fa-calendar-alt"></i>
                            {{ $post->published_at ? $post->published_at->format('M d, Y') : $post->created_at->format('M d, Y') }}
                        </span>
                    </div>

                    <!-- Title -->
                    <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-primary-600 transition">
                        <a href="{{ route('blog.show', $post->slug) }}">
                            {{ $post->title }}
                        </a>
                    </h3>

                    <!-- Excerpt -->
                    @if($post->excerpt)
                        <p class="text-gray-600 text-sm line-clamp-3 mb-4">
                            {{ $post->excerpt }}
                        </p>
                    @endif

                    <!-- Read More -->
                    <a href="{{ route('blog.show', $post->slug) }}" 
                       class="inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-700 transition group/link">
                        Read More 
                        <i class="fas fa-arrow-right ml-2 text-xs group-hover/link:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </article>
            @endforeach
        </div>

        <!-- View All Button -->
        <div class="text-center mt-10">
            <a href="{{ route('blog.index') }}" 
               class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition font-medium">
                <span>{{ setting('homepage.blog_button', 'View All Posts') }}</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
@endif
