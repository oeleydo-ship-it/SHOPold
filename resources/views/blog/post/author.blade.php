{{-- Blog post: author box. Shared by every theme; replace at themes/<slug>/blog/post/author.blade.php --}}
            <!-- Author Box -->
            <div class="bg-white rounded-2xl shadow-sm p-8 mb-8">
                <div class="flex items-start gap-6">
                    <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user text-gray-400 text-2xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-lg">{{ $post->author->name ?? 'Admin' }}</h4>
                        <p class="text-gray-600 mt-2">
                            Author at {{ setting('store.name', config('app.name')) }}
                        </p>
                    </div>
                </div>
            </div>
