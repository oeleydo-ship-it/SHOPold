{{-- Blog list: categories list and newsletter box. Shared by every theme; replace at themes/<slug>/blog/blocks/sidebar.blade.php --}}
            <!-- Sidebar -->
            <aside class="lg:w-1/3">
                <!-- Categories -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <h3 class="font-bold text-lg mb-4">Categories</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="{{ route('blog.index') }}" 
                               class="flex items-center justify-between py-2 px-3 rounded-lg {{ !isset($category) ? 'bg-primary-50 text-primary-600' : 'hover:bg-gray-50' }}">
                                <span>All Posts</span>
                                <span class="text-sm text-gray-500">{{ $posts->total() }}</span>
                            </a>
                        </li>
                        @foreach($categories as $cat)
                        <li>
                            <a href="{{ route('blog.category', $cat->slug) }}" 
                               class="flex items-center justify-between py-2 px-3 rounded-lg {{ isset($category) && $category->id == $cat->id ? 'bg-primary-50 text-primary-600' : 'hover:bg-gray-50' }}">
                                <span>{{ $cat->name }}</span>
                                <span class="text-sm text-gray-500">{{ $cat->published_posts_count }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Newsletter -->
                @if(setting('features.newsletter_enabled'))
                <div class="bg-gradient-to-br from-primary-600 to-primary-800 rounded-xl shadow-sm p-6 text-white">
                    <h3 class="font-bold text-lg mb-2">Subscribe to Newsletter</h3>
                    <p class="text-primary-100 text-sm mb-4">Get the latest posts delivered to your inbox</p>
                    <form action="{{ route('newsletter.subscribe') }}" method="POST">
                        @csrf
                        <input type="email" name="email" placeholder="Your email" required
                               class="w-full px-4 py-2 rounded-lg text-gray-900 mb-3">
                        <button type="submit" class="w-full py-2 bg-white text-primary-600 font-semibold rounded-lg hover:bg-gray-100">
                            Subscribe
                        </button>
                    </form>
                </div>
                @endif
            </aside>
