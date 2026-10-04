{{-- Blog list: post cards grid with pagination and the empty state. Shared by every theme; replace at themes/<slug>/blog/blocks/posts.blade.php --}}
                <!-- Posts Grid -->
                @if($posts->count() > 0)
                <div class="grid md:grid-cols-2 gap-6">
                    @foreach($posts as $post)
                    <article class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-lg transition group">
                        <a href="{{ route('blog.show', $post->slug) }}">
                            @if($post->featured_image)
                            <div class="aspect-video overflow-hidden">
                                <img src="{{ $post->image_url }}" alt="{{ $post->title }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </div>
                            @else
                            <div class="aspect-video bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                                <i class="fas fa-newspaper text-white text-4xl opacity-50"></i>
                            </div>
                            @endif
                        </a>
                        <div class="p-6">
                            @if($post->category)
                            <a href="{{ route('blog.category', $post->category->slug) }}" 
                               class="text-xs font-semibold text-primary-600 uppercase tracking-wide hover:underline">
                                {{ $post->category->name }}
                            </a>
                            @endif
                            <h2 class="mt-2 text-xl font-bold text-gray-900 group-hover:text-primary-600 transition">
                                <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                            </h2>
                            <p class="mt-3 text-gray-600 line-clamp-3">
                                {{ $post->excerpt ?: Str::limit(strip_tags($post->content), 150) }}
                            </p>
                            <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-2">
                                        <i class="fas fa-user text-gray-400 text-xs"></i>
                                    </div>
                                    <span>{{ $post->author->name ?? 'Admin' }}</span>
                                </div>
                                <span>{{ $post->published_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $posts->links() }}
                </div>
                @else
                <div class="text-center py-12 bg-white rounded-xl">
                    <i class="fas fa-newspaper text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-700">No posts found</h3>
                    <p class="text-gray-500 mt-2">Check back later for new content</p>
                </div>
                @endif
