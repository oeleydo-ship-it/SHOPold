{{-- Blog post: comments and comment form. Shared by every theme; replace at themes/<slug>/blog/post/comments.blade.php --}}
            <!-- Comments Section -->
            @if($post->allow_comments && setting('features.blog_comments'))
            <div id="comments" class="mt-12 bg-white rounded-2xl shadow-sm p-8">
                <h3 class="text-2xl font-bold mb-6">Comments ({{ $post->approvedComments->count() }})</h3>
                
                @if($post->approvedComments->count() > 0)
                <div class="space-y-6 mb-8">
                    @foreach($post->approvedComments as $comment)
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="font-semibold">{{ $comment->user->name ?? $comment->author_name ?? 'Guest' }}</span>
                                <span class="text-sm text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-gray-600">{{ $comment->content }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Comment Form -->
                <div class="border-t pt-8">
                    <h4 class="font-bold mb-4">Leave a Comment</h4>
                    <p class="text-gray-500 text-sm mb-4">Comments are moderated and will appear after approval.</p>
                    <form action="{{ route('blog.comments.store', $post->slug) }}" method="POST" class="space-y-4">
                        @csrf
                        {{-- spam trap: hidden from people, bots tend to fill it in --}}
                        <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                        @auth
                        <p class="text-sm text-gray-600">Commenting as <strong>{{ auth()->user()->name }}</strong></p>
                        @endauth
                        @guest
                        <div class="grid md:grid-cols-2 gap-4">
                            <input type="text" name="author_name" value="{{ old('author_name') }}" maxlength="100" placeholder="Your Name *" required aria-label="Your name"
                                   class="px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary-500">
                            <input type="email" name="author_email" value="{{ old('author_email') }}" maxlength="255" placeholder="Your Email *" required aria-label="Your email"
                                   class="px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary-500">
                        </div>
                        @endguest
                        @if($errors->hasAny(['author_name', 'author_email', 'content']))
                        <ul class="text-sm text-red-600 space-y-1">
                            @foreach(['author_name', 'author_email', 'content'] as $field)
                                @error($field)<li>{{ $message }}</li>@enderror
                            @endforeach
                        </ul>
                        @endif
                        <textarea name="content" rows="4" maxlength="2000" placeholder="Your comment *" required aria-label="Your comment"
                                  class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary-500">{{ old('content') }}</textarea>
                        <button type="submit" class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700">
                            Post Comment
                        </button>
                    </form>
                </div>
            </div>
            @endif
