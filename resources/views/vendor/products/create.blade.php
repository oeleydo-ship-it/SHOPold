@extends('layouts.vendor')

@section('title', 'Add Product')
@section('page-title', 'Add New Product')

@section('content')
<form action="{{ route('vendor.products.store') }}" method="POST" enctype="multipart/form-data" x-data="productForm()">
    @csrf
    
    <!-- Product Type Selection -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Product Type</h3>
        <div class="grid grid-cols-2 {{ setting('features.digital_products') ? 'md:grid-cols-3' : '' }} gap-4">
            <label class="relative cursor-pointer">
                <input type="radio" name="product_type" value="simple" x-model="productType" @change="window.dispatchEvent(new CustomEvent('product-type-changed', {detail: 'simple'}))" class="peer sr-only" checked>
                <div class="p-4 border-2 rounded-xl text-center transition"
                     :class="productType === 'simple' ? 'border-vendor-500 bg-vendor-50' : 'border-gray-200 hover:bg-gray-50'">
                    <i class="fas fa-cube text-2xl mb-2 text-gray-600"></i>
                    <div class="font-medium">Simple Product</div>
                    <div class="text-xs text-gray-500">Single product without variations</div>
                </div>
            </label>
            <label class="relative cursor-pointer">
                <input type="radio" name="product_type" value="variable" x-model="productType" @change="window.dispatchEvent(new CustomEvent('product-type-changed', {detail: 'variable'}))" class="peer sr-only">
                <div class="p-4 border-2 rounded-xl text-center transition"
                     :class="productType === 'variable' ? 'border-vendor-500 bg-vendor-50' : 'border-gray-200 hover:bg-gray-50'">
                    <i class="fas fa-layer-group text-2xl mb-2 text-gray-600"></i>
                    <div class="font-medium">Variable Product</div>
                    <div class="text-xs text-gray-500">Has options like size, color</div>
                </div>
            </label>
            @if(setting('features.digital_products'))
            <label class="relative cursor-pointer">
                <input type="radio" name="product_type" value="digital" x-model="productType" @change="window.dispatchEvent(new CustomEvent('product-type-changed', {detail: 'digital'}))" class="peer sr-only">
                <div class="p-4 border-2 rounded-xl text-center transition"
                     :class="productType === 'digital' ? 'border-cyan-500 bg-cyan-50' : 'border-gray-200 hover:bg-gray-50'">
                    <i class="fas fa-download text-2xl mb-2 text-cyan-600"></i>
                    <div class="font-medium">Digital Product</div>
                    <div class="text-xs text-gray-500">Downloadable files</div>
                </div>
            </label>
            @endif
        </div>
    </div>
    
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Basic Information</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500 @error('name') border-red-500 @enderror">
                        @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                            <input type="text" name="sku" value="{{ old('sku') }}"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                            <p class="text-xs text-gray-500 mt-1">Leave empty for auto-generate</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
                            <input type="text" name="barcode" value="{{ old('barcode') }}"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
                        <textarea name="short_description" rows="2" maxlength="500"
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">{{ old('short_description') }}</textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Description *</label>
                        <textarea name="description" rows="5" required
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">{{ old('description') }}</textarea>
                        @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Pricing</h3>
                
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">{{ current_currency_symbol() }}</span>
                            <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0" required
                                   class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500 @error('price') border-red-500 @enderror">
                        </div>
                        @error('price')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">{{ current_currency_symbol() }}</span>
                            <input type="number" name="sale_price" value="{{ old('sale_price') }}" step="0.01" min="0"
                                   class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cost Price</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">{{ current_currency_symbol() }}</span>
                            <input type="number" name="cost_price" value="{{ old('cost_price') }}" step="0.01" min="0"
                                   class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">For profit calculation</p>
                    </div>
                </div>
                
                <!-- Sale Schedule -->
                <div class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg" x-data="{ showSchedule: false }">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-orange-900"><i class="fas fa-clock mr-2"></i>Sale Schedule</h4>
                            <p class="text-sm text-orange-700">Set start and end dates for the sale price</p>
                        </div>
                        <button type="button" @click="showSchedule = !showSchedule" class="text-orange-600 hover:text-orange-800 text-sm">
                            <span x-text="showSchedule ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>
                    <div x-show="showSchedule" x-collapse class="mt-4 grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sale Start Date</label>
                            <input type="datetime-local" name="sale_start_date" value="{{ old('sale_start_date') }}" class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sale End Date</label>
                            <input type="datetime-local" name="sale_end_date" value="{{ old('sale_end_date') }}" class="w-full px-4 py-2 border rounded-lg">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory (for Simple Products only - not for digital) -->
            <div x-show="productType === 'simple'" x-cloak class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Inventory</h3>
                
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity *</label>
                        <input type="number" name="quantity" value="{{ old('quantity', 0) }}" min="0" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500 @error('quantity') border-red-500 @enderror">
                        @error('quantity')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Low Stock Alert</label>
                        <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', 5) }}" min="0"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    </div>
                    
                    <div class="flex items-end">
                        <label class="flex items-center">
                            <input type="checkbox" name="manage_stock" value="1" {{ old('manage_stock', true) ? 'checked' : '' }} class="rounded text-vendor-600">
                            <span class="ml-2 text-sm">Track Inventory</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-4 grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                        <input type="number" name="weight" value="{{ old('weight') }}" step="0.01" min="0"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dimensions (LxWxH)</label>
                        <input type="text" name="dimensions" value="{{ old('dimensions') }}" placeholder="10x5x3"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    </div>
                </div>
            </div>

            <!-- Digital Products Info -->
            <div x-show="productType === 'digital'" x-cloak class="bg-cyan-50 border border-cyan-200 rounded-xl p-6">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-infinity text-cyan-600"></i>
                    </div>
                    <div>
                        <h4 class="font-semibold text-cyan-900">Unlimited Availability</h4>
                        <p class="text-sm text-cyan-700 mt-1">Digital products don't require inventory tracking. They are always available for purchase when active.</p>
                    </div>
                </div>
            </div>

            <!-- Attributes (for Variable Products) -->
            <div x-show="productType === 'variable'" x-collapse class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-sliders-h mr-2 text-gray-400"></i>Product Attributes
                </h3>
                <p class="text-sm text-gray-500 mb-4">Select attributes to use for variations (e.g., Size, Color)</p>
                
                @if($attributes->isEmpty())
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-yellow-800"><i class="fas fa-exclamation-triangle mr-2"></i>No attributes available. Please contact admin to create product attributes.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($attributes as $attribute)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="attributes[]" value="{{ $attribute->id }}" 
                                           x-model="selectedAttributes"
                                           @change="toggleAttribute({{ $attribute->id }})"
                                           class="rounded text-vendor-600 mr-2">
                                    <span class="font-medium">{{ $attribute->name }}</span>
                                    @if($attribute->type)
                                    <span class="ml-2 text-xs px-2 py-0.5 bg-gray-100 rounded">{{ ucfirst($attribute->type) }}</span>
                                    @endif
                                </label>
                            </div>
                            <div x-show="selectedAttributes.includes('{{ $attribute->id }}')" x-collapse class="mt-3 pl-6">
                                <p class="text-sm text-gray-500 mb-2">Select values for this attribute:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($attribute->values as $value)
                                        <label class="inline-flex items-center px-3 py-1.5 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                            <input type="checkbox" name="attribute_values[{{ $attribute->id }}][]" value="{{ $value->id }}" 
                                                   class="rounded text-vendor-600 mr-2">
                                            @if($attribute->type === 'color' && $value->color_code)
                                                <span class="w-4 h-4 rounded-full border mr-2" style="background-color: {{ $value->color_code }}"></span>
                                            @endif
                                            <span class="text-sm">{{ $value->value }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800"><i class="fas fa-info-circle mr-2"></i>After creating the product, you can manage variations from the edit page.</p>
                    </div>
                @endif
            </div>

            <!-- Images -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Product Images</h3>
                
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center" 
                     x-data="{ dragging: false }"
                     @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="dragging = false"
                     :class="{ 'border-vendor-500 bg-vendor-50': dragging }">
                    <input type="file" name="images[]" multiple accept="image/*" class="hidden" id="images"
                           onchange="previewImages(this)">
                    <label for="images" class="cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 mb-2">Drag and drop images here, or click to browse</p>
                        <p class="text-xs text-gray-500">PNG, JPG up to 2MB each. First image will be the main image.</p>
                    </label>
                </div>
                
                <div id="imagePreview" class="grid grid-cols-4 gap-4 mt-4"></div>
            </div>

            <!-- Digital Files Section (for digital products) -->
            @if(setting('features.digital_products'))
            <div x-show="productType === 'digital'" x-collapse class="bg-white rounded-xl shadow-sm p-6" x-data="digitalFilesCreate()">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-download mr-2 text-gray-400"></i>Digital Files</h3>
                        <p class="text-sm text-gray-500">Upload files or add URLs for customers to download after purchase</p>
                    </div>
                    <button type="button" @click="addNewFile()" class="px-4 py-2 bg-cyan-600 text-white text-sm rounded-lg hover:bg-cyan-700">
                        <i class="fas fa-plus mr-1"></i>Add File
                    </button>
                </div>

                {{-- New Files to be added (pending) --}}
                <template x-for="(file, index) in newFiles" :key="index">
                    <div class="flex items-center justify-between p-4 border rounded-lg bg-cyan-50 mb-3">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="file.file_type === 'url' ? 'bg-purple-100 text-purple-600' : 'bg-cyan-100 text-cyan-600'">
                                <i class="fas" :class="file.file_type === 'url' ? 'fa-link' : 'fa-file-plus'"></i>
                            </div>
                            <div>
                                <div class="font-medium" x-text="file.name"></div>
                                <div class="text-sm text-gray-500">
                                    <span x-show="file.file_type === 'upload'" x-text="file.fileName"></span>
                                    <span x-show="file.file_type === 'url'" x-text="file.file_url" class="truncate max-w-xs"></span>
                                    <span class="ml-2 text-cyan-600"><i class="fas fa-clock mr-1"></i>Will be saved with product</span>
                                </div>
                            </div>
                        </div>
                        <button type="button" @click="removeNewFile(index)" class="p-2 text-red-600 hover:bg-red-50 rounded" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </template>

                {{-- Empty State --}}
                <div x-show="!showAddForm && newFiles.length === 0" class="text-center py-8 text-gray-500 border-2 border-dashed rounded-lg mb-4">
                    <i class="fas fa-cloud-upload-alt text-4xl mb-2"></i>
                    <p>No digital files yet. Click "Add File" to upload.</p>
                </div>

                {{-- Add File Form --}}
                <div x-show="showAddForm" x-collapse class="border-2 border-dashed border-cyan-300 rounded-lg p-4 bg-cyan-50 mt-4">
                    <h4 class="font-medium mb-4">Add Digital File</h4>
                    
                    <div class="space-y-4">
                        {{-- File Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Display Name *</label>
                            <input type="text" x-model="fileForm.name" class="w-full px-4 py-2 border rounded-lg" placeholder="e.g., Product Manual PDF">
                        </div>

                        {{-- File Type Selection --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File Source *</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="relative cursor-pointer">
                                    <input type="radio" x-model="fileForm.file_type" value="upload" class="peer sr-only">
                                    <div class="p-4 border-2 rounded-lg text-center peer-checked:border-cyan-500 peer-checked:bg-cyan-50 hover:bg-gray-50 transition">
                                        <i class="fas fa-upload text-xl mb-1 text-gray-600"></i>
                                        <div class="font-medium text-sm">Upload File</div>
                                        <div class="text-xs text-gray-500">Upload from your computer</div>
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" x-model="fileForm.file_type" value="url" class="peer sr-only">
                                    <div class="p-4 border-2 rounded-lg text-center peer-checked:border-cyan-500 peer-checked:bg-cyan-50 hover:bg-gray-50 transition">
                                        <i class="fas fa-link text-xl mb-1 text-gray-600"></i>
                                        <div class="font-medium text-sm">External URL</div>
                                        <div class="text-xs text-gray-500">Link to external file</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- File Upload --}}
                        <div x-show="fileForm.file_type === 'upload'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">File *</label>
                            <input type="file" x-ref="fileInput" @change="handleFileSelect" class="w-full px-4 py-2 border rounded-lg bg-white">
                            <p class="text-xs text-gray-500 mt-1">Max file size: 500MB. Supported: PDF, ZIP, RAR, MP3, MP4, etc.</p>
                        </div>

                        {{-- File URL --}}
                        <div x-show="fileForm.file_type === 'url'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">File URL *</label>
                            <input type="url" x-model="fileForm.file_url" class="w-full px-4 py-2 border rounded-lg" placeholder="https://example.com/file.pdf">
                            <p class="text-xs text-gray-500 mt-1">Direct link to the downloadable file (Dropbox, Google Drive, S3, etc.)</p>
                        </div>

                        {{-- Download Limits --}}
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Download Limit</label>
                                <input type="number" x-model="fileForm.download_limit" min="1" class="w-full px-4 py-2 border rounded-lg" placeholder="Unlimited">
                                <p class="text-xs text-gray-500 mt-1">Leave empty for unlimited</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Expiry (Days)</label>
                                <input type="number" x-model="fileForm.download_expiry_days" min="1" class="w-full px-4 py-2 border rounded-lg" placeholder="Never expires">
                                <p class="text-xs text-gray-500 mt-1">Days after purchase</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" @click="cancelForm()" class="px-4 py-2 border rounded-lg hover:bg-gray-100">Cancel</button>
                        <button type="button" @click="confirmAddFile()" class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700">
                            <i class="fas fa-plus mr-1"></i>Add to List
                        </button>
                    </div>
                </div>

                {{-- Hidden inputs for new files (will be submitted with main form) --}}
                <template x-for="(file, index) in newFiles" :key="'input-' + index">
                    <div>
                        <input type="hidden" :name="'new_digital_files[' + index + '][name]'" :value="file.name">
                        <input type="hidden" :name="'new_digital_files[' + index + '][file_type]'" :value="file.file_type">
                        <input type="hidden" :name="'new_digital_files[' + index + '][file_url]'" :value="file.file_url">
                        <input type="hidden" :name="'new_digital_files[' + index + '][download_limit]'" :value="file.download_limit">
                        <input type="hidden" :name="'new_digital_files[' + index + '][download_expiry_days]'" :value="file.download_expiry_days">
                    </div>
                </template>

                {{-- Info message --}}
                <div x-show="newFiles.length > 0" class="mt-4 p-3 bg-cyan-100 border border-cyan-200 rounded-lg">
                    <p class="text-sm text-cyan-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        You have <span x-text="newFiles.length"></span> file(s) ready to be uploaded. They will be saved when you create the product.
                    </p>
                </div>
            </div>
            @endif

            <!-- Product Features -->
            @include('admin.products.partials.features')

            <!-- SEO -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-search mr-2 text-gray-400"></i>SEO Settings
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                        <input type="text" name="meta_title" value="{{ old('meta_title') }}" maxlength="60" class="w-full px-4 py-2 border rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use product name. Max 60 characters.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                        <textarea name="meta_description" rows="2" maxlength="160" class="w-full px-4 py-2 border rounded-lg">{{ old('meta_description') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use short description. Max 160 characters.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords') }}" class="w-full px-4 py-2 border rounded-lg" placeholder="keyword1, keyword2, keyword3">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Submit -->
            <div class="bg-white rounded-xl shadow-sm p-6 sticky top-4">
                <button type="submit" class="w-full py-3 bg-vendor-600 text-white font-semibold rounded-lg hover:bg-vendor-700 transition">
                    <i class="fas fa-plus mr-2"></i>Create Product
                </button>
                <p class="text-xs text-gray-500 text-center mt-3">
                    @if(setting('features.vendor_products_approval', true))
                    Products require admin approval before being visible
                    @else
                    Product will be active immediately
                    @endif
                </p>
            </div>

            <!-- Category -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Category *</h3>
                <select name="category_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500 @error('category_id') border-red-500 @enderror">
                    <option value="">Select Category</option>
                    @foreach($categories as $category)
                    <optgroup label="{{ $category->name }}">
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @foreach($category->children as $child)
                        <option value="{{ $child->id }}" {{ old('category_id') == $child->id ? 'selected' : '' }}>
                            — {{ $child->name }}
                        </option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
                @error('category_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Brand -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Brand</h3>
                <select name="brand_id" class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    <option value="">No Brand</option>
                    @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                        {{ $brand->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Options</h3>
                <label class="flex items-center mb-3">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="rounded text-vendor-600">
                    <span class="ml-2 text-sm">Featured Product</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="is_virtual" value="1" {{ old('is_virtual') ? 'checked' : '' }} class="rounded text-vendor-600">
                    <span class="ml-2 text-sm">Virtual (No shipping)</span>
                </label>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function productForm() {
    return {
        productType: '{{ old('product_type', 'simple') }}',
        selectedAttributes: [],
        
        toggleAttribute(attrId) {
            const idx = this.selectedAttributes.indexOf(String(attrId));
            if (idx > -1) {
                this.selectedAttributes.splice(idx, 1);
            } else {
                this.selectedAttributes.push(String(attrId));
            }
        }
    }
}

function digitalFilesCreate() {
    return {
        showAddForm: false,
        newFiles: [],
        fileForm: {
            name: '',
            file_type: 'upload',
            file: null,
            fileName: '',
            file_url: '',
            download_limit: '',
            download_expiry_days: '',
        },

        addNewFile() {
            this.showAddForm = true;
            this.resetForm();
        },

        handleFileSelect(e) {
            const file = e.target.files[0];
            if (file) {
                this.fileForm.file = file;
                this.fileForm.fileName = file.name;
                if (!this.fileForm.name) {
                    this.fileForm.name = file.name.replace(/\.[^/.]+$/, '');
                }
            }
        },

        confirmAddFile() {
            // Validate
            if (!this.fileForm.name) {
                alert('Please enter a display name');
                return;
            }
            if (this.fileForm.file_type === 'upload' && !this.fileForm.file) {
                alert('Please select a file to upload');
                return;
            }
            if (this.fileForm.file_type === 'url' && !this.fileForm.file_url) {
                alert('Please enter a file URL');
                return;
            }

            // Add to list
            this.newFiles.push({
                name: this.fileForm.name,
                file_type: this.fileForm.file_type,
                file: this.fileForm.file,
                fileName: this.fileForm.fileName,
                file_url: this.fileForm.file_url,
                download_limit: this.fileForm.download_limit,
                download_expiry_days: this.fileForm.download_expiry_days,
            });

            // If it's a file upload, we need to add it to the form
            if (this.fileForm.file_type === 'upload' && this.fileForm.file) {
                const fileIndex = this.newFiles.length - 1;
                const form = this.$el.closest('form');
                if (form) {
                    const input = document.createElement('input');
                    input.type = 'file';
                    input.name = `new_digital_files[${fileIndex}][file]`;
                    input.style.display = 'none';
                    input.classList.add('digital-file-input');
                    
                    const dt = new DataTransfer();
                    dt.items.add(this.fileForm.file);
                    input.files = dt.files;
                    
                    form.appendChild(input);
                }
            }

            this.showAddForm = false;
            this.resetForm();
        },

        removeNewFile(index) {
            this.newFiles.splice(index, 1);
            const form = this.$el.closest('form');
            if (form) {
                const inputs = form.querySelectorAll('.digital-file-input');
                if (inputs[index]) {
                    inputs[index].remove();
                }
            }
        },

        cancelForm() {
            this.showAddForm = false;
            this.resetForm();
        },

        resetForm() {
            this.fileForm = {
                name: '',
                file_type: 'upload',
                file: null,
                fileName: '',
                file_url: '',
                download_limit: '',
                download_expiry_days: '',
            };
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        }
    };
}

function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg">
                    ${index === 0 ? '<span class="absolute top-1 left-1 bg-vendor-600 text-white text-xs px-2 py-0.5 rounded">Main</span>' : ''}
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}
</script>
@endpush
