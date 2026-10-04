@props([
    'name' => 'image',
    'value' => '',
    'multiple' => false,
    'collection' => 'products',
    'label' => 'Select Image'
])

<div x-data="mediaPicker_{{ str_replace(['[', ']', '.'], '_', $name) }}()" class="media-picker">
    <!-- Selected Images Display -->
    @if($multiple)
    <div class="grid grid-cols-3 gap-2 mb-3" x-show="selectedImages.length > 0">
        <template x-for="(img, index) in selectedImages" :key="index">
            <div class="relative group aspect-square bg-gray-100 rounded-lg overflow-hidden border">
                <img :src="getImageUrl(img)" class="w-full h-full object-cover">
                <button type="button" @click.prevent.stop="removeImage(index)" 
                        class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
                <input type="hidden" name="{{ $name }}[]" :value="getImagePath(img)">
            </div>
        </template>
    </div>
    @else
    <div x-show="selectedImage" class="relative mb-3 inline-block">
        <div class="w-32 h-32 bg-gray-100 rounded-lg overflow-hidden border">
            <img :src="getImageUrl(selectedImage)" class="w-full h-full object-cover" x-show="selectedImage">
        </div>
        <button type="button" @click.prevent.stop="selectedImage = null" 
                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center">
            <i class="fas fa-times text-xs"></i>
        </button>
        <input type="hidden" name="{{ $name }}" :value="getImagePath(selectedImage)">
    </div>
    @endif

    <!-- Select Button -->
    <button type="button" @click="openPicker()" 
            class="px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition flex items-center gap-2 text-gray-600">
        <i class="fas fa-images"></i>
        <span>{{ $label }}</span>
    </button>

    <!-- Media Picker Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="showModal = false">
        <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-4xl w-full flex flex-col" style="max-height: calc(100vh - 100px);">
                <!-- Modal Header -->
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-bold text-gray-900">Media Library</h3>
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <input type="text" x-model="searchQuery" @input.debounce.300ms="loadMedia()" 
                                   placeholder="Search..." class="pl-8 pr-4 py-1.5 border rounded-lg text-sm w-48">
                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        </div>
                        <button type="button" @click.prevent="showModal = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex border-b">
                    <button type="button" @click.prevent="activeTab = 'library'" 
                            :class="activeTab === 'library' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'"
                            class="px-4 py-3 border-b-2 font-medium">
                        Media Library
                    </button>
                    <button type="button" @click.prevent="activeTab = 'upload'" 
                            :class="activeTab === 'upload' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500'"
                            class="px-4 py-3 border-b-2 font-medium">
                        Upload New
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto p-4">
                    <!-- Library Tab -->
                    <div x-show="activeTab === 'library'">
                        <template x-if="loading">
                            <div class="flex items-center justify-center py-12">
                                <i class="fas fa-spinner fa-spin text-2xl text-primary-600"></i>
                            </div>
                        </template>
                        
                        <template x-if="!loading && mediaItems.length === 0">
                            <div class="text-center py-12">
                                <i class="fas fa-images text-5xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">No images found. Upload some first!</p>
                            </div>
                        </template>

                        <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-3" x-show="!loading && mediaItems.length > 0">
                            <template x-for="item in mediaItems" :key="item.id">
                                <div @click.prevent="toggleSelection(item)" 
                                     class="aspect-square rounded-lg overflow-hidden cursor-pointer border-2 transition relative"
                                     :class="isSelected(item) ? 'border-primary-500 ring-2 ring-primary-200' : 'border-transparent hover:border-gray-300'">
                                    <img :src="item.url" :alt="item.name" class="w-full h-full object-cover">
                                    <div x-show="isSelected(item)" class="absolute top-1 right-1 w-5 h-5 bg-primary-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Load More -->
                        <div x-show="hasMore" class="text-center mt-4">
                            <button type="button" @click.prevent="loadMore()" class="px-4 py-2 text-primary-600 hover:bg-primary-50 rounded-lg">
                                Load More
                            </button>
                        </div>
                    </div>

                    <!-- Upload Tab -->
                    <div x-show="activeTab === 'upload'">
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center"
                             @dragover.prevent="dragOver = true" @dragleave="dragOver = false" @drop.prevent="handleDrop($event)"
                             :class="dragOver ? 'border-primary-500 bg-primary-50' : ''">
                            <input type="file" :id="'upload_' + pickerId" multiple accept="image/*" class="hidden" @change="handleUpload($event)">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <p class="text-gray-600 mb-2">Drag and drop images here</p>
                            <label :for="'upload_' + pickerId" class="inline-block px-4 py-2 bg-primary-600 text-white rounded-lg cursor-pointer hover:bg-primary-700">
                                Browse Files
                            </label>
                        </div>

                        <!-- Upload Progress -->
                        <div class="mt-4 space-y-2" x-show="uploadingFiles.length > 0">
                            <template x-for="(file, index) in uploadingFiles" :key="index">
                                <div class="flex items-center gap-3 p-2 rounded" :class="file.status === 'error' ? 'bg-red-50' : 'bg-gray-50'">
                                    <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-image text-gray-500 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm truncate" x-text="file.name"></p>
                                        <template x-if="file.status === 'error' && file.error">
                                            <p class="text-xs text-red-600 mt-1" x-text="file.error"></p>
                                        </template>
                                        <template x-if="file.status !== 'error'">
                                            <div class="w-full bg-gray-200 rounded-full h-1 mt-1">
                                                <div class="bg-primary-600 h-1 rounded-full" :style="'width: ' + file.progress + '%'"></div>
                                            </div>
                                        </template>
                                    </div>
                                    <i class="fas fa-check-circle text-green-500" x-show="file.status === 'done'"></i>
                                    <i class="fas fa-times-circle text-red-500" x-show="file.status === 'error'"></i>
                                </div>
                            </template>
                        </div>

                        <!-- Upload Error Message -->
                        <div x-show="uploadError" x-transition class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-red-800">Upload Failed</p>
                                <p class="text-sm text-red-600" x-text="uploadErrorMessage"></p>
                            </div>
                        </div>

                        <!-- Upload Success Message -->
                        <div x-show="uploadSuccess" x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 transform translate-y-2"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-green-800">Upload Successful!</p>
                                <p class="text-sm text-green-600" x-text="uploadSuccessMessage"></p>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between p-4 border-t bg-gray-50 rounded-b-2xl flex-shrink-0">
                <p class="text-sm text-gray-600">
                    <span x-text="tempSelected.length"></span> selected
                </p>
                <div class="flex gap-3">
                    <button type="button" @click.prevent="showModal = false" class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="button" @click.prevent="confirmSelection()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Select
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function mediaPicker_{{ str_replace(['[', ']', '.'], '_', $name) }}() {
    return {
        pickerId: '{{ str_replace(['[', ']', '.'], '_', $name) }}',
        showModal: false,
        activeTab: 'library',
        loading: false,
        mediaItems: [],
        searchQuery: '',
        page: 1,
        hasMore: false,
        tempSelected: [],
        @if($multiple)
        selectedImages: @json($value ? (is_array($value) ? $value : json_decode($value, true) ?? []) : []),
        @else
        selectedImage: @json($value ?: null),
        @endif
        uploadingFiles: [],
        dragOver: false,
        collection: '{{ $collection }}',
        multiple: {{ $multiple ? 'true' : 'false' }},
        uploadSuccess: false,
        uploadSuccessMessage: '',
        uploadError: false,
        uploadErrorMessage: '',

        getImageUrl(img) {
            if (!img) return '';
            // If it's an object with url property
            if (typeof img === 'object' && img.url) return img.url;
            // If it's a string path
            if (typeof img === 'string') {
                // If already a full URL
                if (img.startsWith('http') || img.startsWith('/storage/')) return img;
                // Otherwise prepend /storage/
                return '/storage/' + img;
            }
            return '';
        },

        getImagePath(img) {
            if (!img) return '';
            // If it's an object with path property
            if (typeof img === 'object' && img.path) return img.path;
            // If it's a string, return as is
            if (typeof img === 'string') return img;
            return '';
        },

        openPicker() {
            this.showModal = true;
            this.tempSelected = [];
            @if($multiple)
            this.tempSelected = [...this.selectedImages];
            @else
            if (this.selectedImage) {
                this.tempSelected = [this.selectedImage];
            }
            @endif
            this.loadMedia();
        },

        async loadMedia() {
            this.loading = true;
            this.page = 1;
            const params = new URLSearchParams({
                collection: this.collection,
                search: this.searchQuery
            });
            
            try {
                const response = await fetch(`/admin/media/picker?${params}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                this.mediaItems = data.media;
                this.hasMore = data.pagination.current_page < data.pagination.last_page;
            } catch (error) {
                console.error('Error loading media:', error);
            }
            this.loading = false;
        },

        async loadMore() {
            this.page++;
            const params = new URLSearchParams({
                page: this.page,
                collection: this.collection,
                search: this.searchQuery
            });
            
            try {
                const response = await fetch(`/admin/media/picker?${params}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                this.mediaItems = [...this.mediaItems, ...data.media];
                this.hasMore = data.pagination.current_page < data.pagination.last_page;
            } catch (error) {
                console.error('Error loading more:', error);
            }
        },

        toggleSelection(item) {
            if (this.multiple) {
                const index = this.tempSelected.findIndex(i => (i.id || i) === item.id);
                if (index > -1) {
                    this.tempSelected.splice(index, 1);
                } else {
                    this.tempSelected.push(item);
                }
            } else {
                this.tempSelected = [item];
            }
        },

        isSelected(item) {
            return this.tempSelected.some(i => (i.id || i) === item.id);
        },

        confirmSelection() {
            if (this.multiple) {
                this.selectedImages = this.tempSelected.map(item => ({
                    id: item.id,
                    url: item.url,
                    path: item.path
                }));
            } else {
                this.selectedImage = this.tempSelected[0] || null;
            }
            this.showModal = false;
        },

        removeImage(index) {
            this.selectedImages.splice(index, 1);
        },

        handleDrop(event) {
            this.dragOver = false;
            this.uploadFiles(event.dataTransfer.files);
        },

        handleUpload(event) {
            this.uploadFiles(event.target.files);
        },

        async uploadFiles(files) {
            let successCount = 0;
            let errorCount = 0;
            let lastError = '';
            const maxFileSize = 2 * 1024 * 1024; // 2MB limit for safety
            
            // Reset error state
            this.uploadError = false;
            this.uploadErrorMessage = '';
            
            for (let file of files) {
                const fileObj = { name: file.name, progress: 0, status: 'uploading', error: null };
                this.uploadingFiles.push(fileObj);
                
                // Check file size before uploading
                if (file.size > maxFileSize) {
                    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    fileObj.status = 'error';
                    fileObj.error = `File too large (${sizeMB}MB). Maximum size is 2MB.`;
                    lastError = fileObj.error;
                    errorCount++;
                    continue;
                }
                
                const formData = new FormData();
                formData.append('file', file);
                formData.append('collection', this.collection);
                
                try {
                    const response = await fetch('/admin/media', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });
                    
                    // Check content type to ensure we got JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        console.error('Non-JSON response:', text);
                        throw new Error('File too large. Please use images under 2MB.');
                    }
                    
                    const data = await response.json();
                    
                    if (!response.ok || !data.success) {
                        // Extract error message from validation errors or message
                        let errorMsg = data.message || 'Upload failed';
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join(', ');
                        }
                        throw new Error(errorMsg);
                    }
                    
                    fileObj.progress = 100;
                    fileObj.status = 'done';
                    successCount++;
                    
                    if (data.media) {
                        this.mediaItems.unshift(data.media);
                        // Auto-select uploaded image
                        this.toggleSelection(data.media);
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    fileObj.status = 'error';
                    fileObj.error = error.message;
                    lastError = error.message;
                    errorCount++;
                }
            }
            
            // Show success message
            if (successCount > 0) {
                this.uploadSuccess = true;
                this.uploadSuccessMessage = successCount === 1 
                    ? 'Image uploaded successfully and added to library.' 
                    : `${successCount} images uploaded successfully and added to library.`;
                
                // Hide success message after 4 seconds
                setTimeout(() => { 
                    this.uploadSuccess = false; 
                }, 4000);
            }
            
            // Show error message if any uploads failed
            if (errorCount > 0) {
                this.uploadError = true;
                this.uploadErrorMessage = errorCount === 1 
                    ? lastError 
                    : `${errorCount} files failed to upload. ${lastError}`;
                
                // Hide error message after 6 seconds
                setTimeout(() => { 
                    this.uploadError = false; 
                }, 6000);
            }
            
            setTimeout(() => { this.uploadingFiles = []; }, 2000);
        }
    }
}
</script>
