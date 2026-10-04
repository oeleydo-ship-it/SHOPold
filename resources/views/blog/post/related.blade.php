{{-- Blog post: related articles. Shared by every theme; replace at themes/<slug>/blog/post/related.blade.php --}}
            <!-- Related Posts -->
            @if($relatedPosts->count() > 0)
            <div class="mt-12">
                <h3 class="text-2xl font-bold mb-6">Related Articles</h3>
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach($relatedPosts as $related)
                    <article class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-lg transition group">
                        <a href="{{ route('blog.show', $related->slug) }}">
                            @if($related->featured_image)
                            <div class="aspect-video overflow-hidden">
                                <img src="{{ $related->image_url }}" alt="{{ $related->title }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </div>
                            @else
                            <div class="aspect-video bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                                <i class="fas fa-newspaper text-white text-3xl opacity-50"></i>
                            </div>
                            @endif
                        </a>
                        <div class="p-4">
                            <h4 class="font-bold text-gray-900 group-hover:text-primary-600 transition line-clamp-2">
                                <a href="{{ route('blog.show', $related->slug) }}">{{ $related->title }}</a>
                            </h4>
                            <p class="text-sm text-gray-500 mt-2">{{ $related->published_at->format('M d, Y') }}</p>
                        </div>
                    </article>
                    @endforeach
                </div>
            </div>
            @endif
