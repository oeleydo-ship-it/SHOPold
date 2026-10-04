{{-- Product page: Image gallery with zoom, arrows, fullscreen and thumbnails (Alpine state is local to this block). Shared by every theme; a theme can replace it at themes/<slug>/shop/product/gallery.blade.php --}}
            <!-- Product Images Gallery -->
            @php
                $allImages = [];
                
                // Add main image first if exists
                if ($product->image) {
                    $allImages[] = $product->image_url;
                }
                
                // Get images from ProductImage relation
                if ($product->images && $product->images->count() > 0) {
                    foreach ($product->images as $img) {
                        $imgUrl = $img->image_url ?? asset('storage/' . $img->image);
                        if (!in_array($imgUrl, $allImages)) {
                            $allImages[] = $imgUrl;
                        }
                    }
                }
                
                // Get images from gallery JSON field
                if ($product->gallery && is_array($product->gallery) && count($product->gallery) > 0) {
                    foreach ($product->gallery as $img) {
                        $imgUrl = asset('storage/' . $img);
                        if (!in_array($imgUrl, $allImages)) {
                            $allImages[] = $imgUrl;
                        }
                    }
                }
                
                // Default placeholder if no images
                if (empty($allImages)) {
                    $allImages[] = asset('images/no-image.png');
                }
                $mainImage = $allImages[0];
            @endphp
            <div x-data="{ 
                activeImage: '{{ $mainImage }}',
                activeIndex: 0,
                images: {{ json_encode($allImages) }},
                originalImages: {{ json_encode($allImages) }},
                zoom: false,
                zoomX: 0,
                zoomY: 0,
                updateImageForVariation(variationImage) {
                    if (variationImage) {
                        // Update to show variation image
                        this.activeImage = variationImage;
                        this.activeIndex = 0;
                        this.images = [variationImage];
                    } else {
                        // Reset to original images
                        this.images = this.originalImages;
                        this.activeImage = this.originalImages[0];
                        this.activeIndex = 0;
                    }
                    
                    // Add a subtle fade effect
                    const imgElement = this.$el.querySelector('img');
                    if (imgElement) {
                        imgElement.style.opacity = '0.7';
                        setTimeout(() => {
                            imgElement.style.opacity = '1';
                        }, 150);
                    }
                }
            }" 
            @variation-changed.window="updateImageForVariation($event.detail.image)"
            >
                <!-- Main Image with Zoom -->
                <div class="relative aspect-square bg-gray-50 rounded-xl overflow-hidden mb-4 cursor-zoom-in"
                     @mouseenter="zoom = true"
                     @mouseleave="zoom = false"
                     @mousemove="zoomX = ($event.offsetX / $el.offsetWidth) * 100; zoomY = ($event.offsetY / $el.offsetHeight) * 100">
                    <img :src="activeImage" 
                         alt="{{ $product->translated_name }}" 
                         class="w-full h-full object-contain transition-all duration-200"
                         :style="zoom ? `transform: scale(2); transform-origin: ${zoomX}% ${zoomY}%` : ''"
                         style="transition: opacity 0.15s ease-in-out;">
                    
                    <!-- Image Navigation Arrows -->
                    <template x-if="images.length > 1">
                        <div>
                            <button @click="activeIndex = (activeIndex - 1 + images.length) % images.length; activeImage = images[activeIndex]"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 rounded-full shadow-lg flex items-center justify-center hover:bg-white transition">
                                <i class="fas fa-chevron-left text-gray-700"></i>
                            </button>
                            <button @click="activeIndex = (activeIndex + 1) % images.length; activeImage = images[activeIndex]"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-white/90 rounded-full shadow-lg flex items-center justify-center hover:bg-white transition">
                                <i class="fas fa-chevron-right text-gray-700"></i>
                            </button>
                        </div>
                    </template>
                    
                    <!-- Fullscreen Button -->
                    <button @click="$dispatch('open-lightbox', { images: images, index: activeIndex })"
                            class="absolute bottom-2 right-2 w-10 h-10 bg-white/90 rounded-full shadow-lg flex items-center justify-center hover:bg-white transition">
                        <i class="fas fa-expand text-gray-700"></i>
                    </button>
                </div>
                
                <!-- Thumbnail Gallery -->
                <div class="grid grid-cols-5 gap-2" x-show="images.length > 1">
                    <template x-for="(image, index) in images" :key="index">
                        <button type="button" 
                                @click="activeImage = image; activeIndex = index"
                                :class="activeIndex === index ? 'ring-2 ring-primary-500 ring-offset-2' : 'ring-1 ring-gray-200 hover:ring-primary-300'"
                                class="aspect-square bg-gray-50 rounded-lg overflow-hidden transition-all duration-200">
                            <img :src="image" :alt="`{{ $product->translated_name }} - Image ${index + 1}`" class="w-full h-full object-contain">
                        </button>
                    </template>
                </div>
                
            </div>
