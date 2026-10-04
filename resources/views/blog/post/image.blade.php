{{-- Blog post: featured image. Shared by every theme; replace at themes/<slug>/blog/post/image.blade.php --}}
            <!-- Featured Image -->
            @if($post->featured_image)
            <div class="mb-8 rounded-2xl overflow-hidden shadow-lg">
                <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-full h-auto">
            </div>
            @endif
