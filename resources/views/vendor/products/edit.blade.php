@extends('layouts.vendor')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')
<form action="{{ route('vendor.products.update', $product) }}" method="POST" enctype="multipart/form-data" x-data="productEditForm()">
    @csrf
    @method('PUT')
    
    <!-- Product Type -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Product Type</h3>
        <div class="grid grid-cols-2 {{ setting('features.digital_products') ? 'md:grid-cols-3' : '' }} gap-4">
            <label class="relative cursor-pointer">
                <input type="radio" name="product_type" value="simple" x-model="productType" @change="window.dispatchEvent(new CustomEvent('product-type-changed', {detail: 'simple'}))" class="peer sr-only">
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
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                            @error('sku')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
                            <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
                        <textarea name="short_description" rows="2" maxlength="500"
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">{{ old('short_description', $product->short_description) }}</textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Description *</label>
                        <textarea name="description" rows="5" required
                                  class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Pricing (for Simple and Digital Products) -->
            <div x-show="productType === 'simple' || productType === 'digital'" x-cloak class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Pricing</h3>
                
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">{{ current_currency_symbol() }}</span>
                            <input type="number" name="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" required
                                   class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">{{ current_currency_symbol() }}</span>
                            <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" step="0.01" min="0"
                                   class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cost Price</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">{{ current_currency_symbol() }}</span>
                            <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0"
                                   class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                    </div>
                </div>
                
                <!-- Sale Schedule -->
                <div class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg" x-data="{ showSchedule: {{ $product->sale_start_date || $product->sale_end_date ? 'true' : 'false' }} }">
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
                            <input type="datetime-local" name="sale_start_date" 
                                   value="{{ old('sale_start_date', $product->sale_start_date ? $product->sale_start_date->format('Y-m-d\TH:i') : '') }}" 
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sale End Date</label>
                            <input type="datetime-local" name="sale_end_date" 
                                   value="{{ old('sale_end_date', $product->sale_end_date ? $product->sale_end_date->format('Y-m-d\TH:i') : '') }}" 
                                   class="w-full px-4 py-2 border rounded-lg">
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
                        <input type="number" name="quantity" value="{{ old('quantity', $product->quantity) }}" min="0" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Low Stock Alert</label>
                        <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 5) }}" min="0"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    </div>
                    
                    <div class="flex items-end">
                        <label class="flex items-center">
                            <input type="checkbox" name="manage_stock" value="1" {{ old('manage_stock', $product->manage_stock) ? 'checked' : '' }} class="rounded text-vendor-600">
                            <span class="ml-2 text-sm">Track Inventory</span>
                        </label>
                    </div>
                </div>
                
                <div class="mt-4 grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                        <input type="number" name="weight" value="{{ old('weight', $product->weight) }}" step="0.01" min="0"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dimensions (LxWxH)</label>
                        <input type="text" name="dimensions" value="{{ old('dimensions', $product->dimensions) }}" placeholder="10x5x3"
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
                <p class="text-sm text-gray-500 mb-4">Select attributes and their values for this product</p>
                
                @if($attributes->isEmpty())
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-yellow-800"><i class="fas fa-exclamation-triangle mr-2"></i>No attributes available. Please contact admin to create product attributes.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($attributes as $attribute)
                        @php
                            $productAttrValues = $product->attributeValues ? $product->attributeValues->where('attribute_id', $attribute->id)->pluck('id')->toArray() : [];
                            $hasAttribute = count($productAttrValues) > 0;
                        @endphp
                        <div class="border rounded-lg p-4" x-data="{ expanded: {{ $hasAttribute ? 'true' : 'false' }} }">
                            <div class="flex items-center justify-between mb-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="attributes[]" value="{{ $attribute->id }}" 
                                           {{ $hasAttribute ? 'checked' : '' }}
                                           @change="expanded = $event.target.checked"
                                           class="rounded text-vendor-600 mr-2">
                                    <span class="font-medium">{{ $attribute->name }}</span>
                                    @if($attribute->type)
                                    <span class="ml-2 text-xs px-2 py-0.5 bg-gray-100 rounded">{{ ucfirst($attribute->type) }}</span>
                                    @endif
                                </label>
                            </div>
                            <div x-show="expanded" x-collapse class="mt-3 pl-6">
                                <p class="text-sm text-gray-500 mb-2">Select values:</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($attribute->values as $value)
                                        <label class="inline-flex items-center px-3 py-1.5 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                            <input type="checkbox" name="attribute_values[{{ $attribute->id }}][]" value="{{ $value->id }}" 
                                                   {{ in_array($value->id, $productAttrValues) ? 'checked' : '' }}
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
                @endif
            </div>

            <!-- Variations Management -->
            <div x-show="productType === 'variable'" x-collapse class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">
                        <i class="fas fa-layer-group mr-2 text-gray-400"></i>Product Variations
                    </h3>
                    <button type="button" @click="addVariation()" class="px-4 py-2 bg-vendor-600 text-white text-sm rounded-lg hover:bg-vendor-700">
                        <i class="fas fa-plus mr-1"></i>Add Variation
                    </button>
                </div>
                
                @if($product->variations->isEmpty())
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center" x-show="variations.length === 0">
                        <i class="fas fa-layer-group text-gray-300 text-3xl mb-2"></i>
                        <p class="text-gray-500">No variations yet. Click "Add Variation" to create one.</p>
                    </div>
                @endif
                
                <!-- Existing Variations -->
                <div class="space-y-4">
                    @foreach($product->variations as $index => $variation)
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium">
                                {{ $variation->variation_name ?: 'Variation #' . ($index + 1) }}
                            </h4>
                            <div class="flex items-center space-x-2">
                                <span class="text-sm {{ $variation->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">
                                    Stock: {{ $variation->quantity }}
                                </span>
                                <a href="{{ route('vendor.products.variations.edit', [$product, $variation->id]) }}" 
                                   class="p-1 text-vendor-600 hover:bg-vendor-50 rounded" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" 
                                        onclick="deleteVariation({{ $variation->id }})" 
                                        class="p-1 text-red-600 hover:bg-red-50 rounded" 
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">SKU:</span>
                                <span class="font-medium ml-1">{{ $variation->sku }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Price:</span>
                                <span class="font-medium ml-1">{{ format_currency($variation->price ?? $product->price) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Sale:</span>
                                <span class="font-medium ml-1">{{ $variation->sale_price ? format_currency($variation->sale_price) : '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500">Status:</span>
                                <span class="ml-1 px-2 py-0.5 rounded text-xs {{ $variation->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $variation->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                        @if($variation->attributeValues && $variation->attributeValues->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach($variation->attributeValues as $attrValue)
                                <span class="px-2 py-0.5 bg-vendor-100 text-vendor-800 rounded text-xs">
                                    {{ $attrValue->attribute->name }}: {{ $attrValue->value }}
                                </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                
                <!-- New Variation Form (inline) -->
                <div x-show="showNewVariation" x-collapse class="mt-4 border-2 border-dashed border-vendor-300 rounded-lg p-4 bg-vendor-50">
                    <h4 class="font-medium mb-3">New Variation</h4>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                            <input type="text" x-model="newVariation.sku" class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                            <input type="number" x-model="newVariation.price" step="0.01" class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price</label>
                            <input type="number" x-model="newVariation.sale_price" step="0.01" class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <input type="number" x-model="newVariation.quantity" min="0" class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                    </div>
                    <div class="mt-3 flex justify-end space-x-2">
                        <button type="button" @click="showNewVariation = false" class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-100">Cancel</button>
                        <button type="button" @click="saveVariation()" class="px-4 py-2 bg-vendor-600 text-white rounded-lg text-sm hover:bg-vendor-700">Save Variation</button>
                    </div>
                </div>
            </div>

            <!-- Current Images -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Product Images</h3>
                
                @if($product->images && $product->images->count() > 0)
                <div class="grid grid-cols-4 gap-4 mb-4">
                    @foreach($product->images as $image)
                    <div class="relative group">
                        <img src="{{ asset('storage/' . $image->image) }}" class="w-full h-24 object-cover rounded-lg">
                        @if($image->is_primary)
                        <span class="absolute top-1 left-1 bg-vendor-600 text-white text-xs px-2 py-0.5 rounded">Main</span>
                        @endif
                        <label class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center cursor-pointer">
                            <input type="checkbox" name="delete_images[]" value="{{ $image->id }}" class="sr-only peer">
                            <span class="text-white text-sm peer-checked:hidden"><i class="fas fa-trash"></i> Delete</span>
                            <span class="text-red-400 text-sm hidden peer-checked:block"><i class="fas fa-check"></i> Marked</span>
                        </label>
                    </div>
                    @endforeach
                </div>
                @elseif($product->image)
                <div class="mb-4">
                    <img src="{{ asset('storage/' . $product->image) }}" class="w-32 h-32 object-cover rounded-lg">
                </div>
                @endif
                
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <input type="file" name="images[]" multiple accept="image/*" class="hidden" id="images" onchange="previewImages(this)">
                    <label for="images" class="cursor-pointer">
                        <i class="fas fa-plus text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-600 text-sm">Add more images</p>
                    </label>
                </div>
                <div id="imagePreview" class="grid grid-cols-4 gap-4 mt-4"></div>
            </div>

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
                        <input type="text" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" maxlength="60" class="w-full px-4 py-2 border rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use product name. Max 60 characters.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                        <textarea name="meta_description" rows="2" maxlength="160" class="w-full px-4 py-2 border rounded-lg">{{ old('meta_description', $product->meta_description) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $product->meta_keywords) }}" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
            </div>

            <!-- Digital Files -->
            @include('admin.products.partials.digital-files')
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Submit -->
            <div class="bg-white rounded-xl shadow-sm p-6 sticky top-4">
                <button type="submit" class="w-full py-3 bg-vendor-600 text-white font-semibold rounded-lg hover:bg-vendor-700 transition">
                    <i class="fas fa-save mr-2"></i>Update Product
                </button>
                <a href="{{ route('vendor.products.index') }}" class="block w-full py-3 mt-2 text-center border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>

            <!-- Status -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Status</h3>
                <div class="flex items-center justify-between mb-4">
                    @if($product->is_active)
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">Active</span>
                    @else
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">Inactive</span>
                    @endif
                    <span class="text-xs text-gray-500">{{ $product->views ?? 0 }} views</span>
                </div>
                <p class="text-xs text-gray-500">Product status is managed by the admin.</p>
            </div>

            <!-- Category -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Category *</h3>
                <select name="category_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    <option value="">Select Category</option>
                    @foreach($categories as $category)
                    <optgroup label="{{ $category->name }}">
                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @foreach($category->children as $child)
                        <option value="{{ $child->id }}" {{ old('category_id', $product->category_id) == $child->id ? 'selected' : '' }}>
                            — {{ $child->name }}
                        </option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>

            <!-- Brand -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Brand</h3>
                <select name="brand_id" class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    <option value="">No Brand</option>
                    @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                        {{ $brand->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Options -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Options</h3>
                <label class="flex items-center mb-3">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }} class="rounded text-vendor-600">
                    <span class="ml-2 text-sm">Featured Product</span>
                </label>
                <label class="flex items-center mb-3">
                    <input type="checkbox" name="is_virtual" value="1" {{ old('is_virtual', $product->is_virtual) ? 'checked' : '' }} class="rounded text-vendor-600">
                    <span class="ml-2 text-sm">Virtual (No shipping)</span>
                </label>
                <label class="flex items-center" x-data>
                    <input type="checkbox" name="is_downloadable" value="1" 
                           {{ old('is_downloadable', $product->is_downloadable) ? 'checked' : '' }} 
                           class="rounded text-vendor-600"
                           @change="$dispatch('downloadable-changed', $event.target.checked); $root.isDownloadable = $event.target.checked">
                    <span class="ml-2 text-sm">Downloadable (Digital Product)</span>
                </label>
            </div>

            <!-- Danger Zone -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-red-200">
                <h3 class="font-semibold text-red-600 mb-2">Danger Zone</h3>
                <p class="text-sm text-gray-500 mb-4">Permanently delete this product.</p>
                <button type="button" onclick="confirmDelete()" class="w-full px-4 py-2 border border-red-500 text-red-500 rounded-lg hover:bg-red-50">
                    <i class="fas fa-trash mr-2"></i>Delete Product
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Delete Form (outside main form) -->
<form id="deleteForm" action="{{ route('vendor.products.destroy', $product) }}" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function productEditForm() {
    return {
        productType: '{{ $product->product_type ?: 'simple' }}',
        showNewVariation: false,
        newVariation: {
            sku: '',
            price: '',
            sale_price: '',
            quantity: 0
        },
        variations: @json($product->variations ?? []),
        isDownloadable: {{ $product->is_downloadable ? 'true' : 'false' }},
        
        addVariation() {
            this.newVariation = {
                sku: '{{ $product->sku }}-' + (this.variations.length + 1),
                price: '{{ $product->price }}',
                sale_price: '',
                quantity: 0
            };
            this.showNewVariation = true;
        },
        
        async saveVariation() {
            if (!this.newVariation.sku) {
                alert('SKU is required');
                return;
            }
            
            try {
                const response = await fetch('{{ route('vendor.products.variations.store', $product) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.newVariation)
                });
                
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    let errorMsg = 'Failed to create variation';
                    if (typeof data.error === 'object') {
                        errorMsg = Object.values(data.error).flat().join('\n');
                    } else if (data.error) {
                        errorMsg = data.error;
                    }
                    alert(errorMsg);
                }
            } catch (error) {
                alert('Error creating variation: ' + error.message);
                console.error(error);
            }
        }
    }
}

// Delete variation function
async function deleteVariation(variationId) {
    if (!confirm('Delete this variation?')) return;
    
    try {
        const response = await fetch(`{{ url('vendor/products/' . $product->id . '/variations') }}/${variationId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            window.location.reload();
        } else {
            const data = await response.json();
            alert(data.error || 'Failed to delete variation');
        }
    } catch (error) {
        alert('Error deleting variation: ' + error.message);
    }
}

function confirmDelete() {
    if (confirm('Are you sure you want to delete this product? This cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}

function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files) {
        Array.from(input.files).forEach((file) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative';
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg">`;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}
</script>
@endpush
