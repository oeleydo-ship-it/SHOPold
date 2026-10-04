@extends('layouts.vendor')

@section('title', 'Edit Variation')
@section('page-title', 'Edit Variation')

@section('content')
<div class="mb-6">
    <a href="{{ route('vendor.products.edit', $product) }}" class="text-vendor-600 hover:text-vendor-800">
        <i class="fas fa-arrow-left mr-2"></i>Back to {{ $product->name }}
    </a>
</div>

<form action="{{ route('vendor.products.variations.update', [$product, $variation->id]) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Variation Details</h3>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                        <input type="text" name="sku" value="{{ old('sku', $variation->sku) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        @error('sku')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                        <input type="number" name="weight" value="{{ old('weight', $variation->weight) }}" step="0.01" min="0"
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Pricing & Stock</h3>
                
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">{{ current_currency_symbol() }}</span>
                            <input type="number" name="price" value="{{ old('price', $variation->price) }}" step="0.01" min="0"
                                   class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use product price ({{ format_currency($product->price) }})</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">{{ current_currency_symbol() }}</span>
                            <input type="number" name="sale_price" value="{{ old('sale_price', $variation->sale_price) }}" step="0.01" min="0"
                                   class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                        <input type="number" name="quantity" value="{{ old('quantity', $variation->quantity) }}" min="0" required
                               class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
                        @error('quantity')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Attributes -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-sliders-h mr-2 text-gray-400"></i>Attribute Values
                </h3>
                <p class="text-sm text-gray-500 mb-4">Select the attribute values for this variation</p>
                
                @if($attributes->isEmpty())
                    <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-yellow-800"><i class="fas fa-exclamation-triangle mr-2"></i>No attributes available.</p>
                    </div>
                @else
                    @php
                        $variationAttrValues = $variation->attributeValues ? $variation->attributeValues->pluck('id')->toArray() : [];
                    @endphp
                    <div class="space-y-4">
                        @foreach($attributes as $attribute)
                        @php
                            $hasValue = $variation->attributeValues ? $variation->attributeValues->where('attribute_id', $attribute->id)->isNotEmpty() : false;
                        @endphp
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium mb-3">{{ $attribute->name }}</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($attribute->values as $value)
                                    <label class="inline-flex items-center px-3 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 transition
                                                  {{ in_array($value->id, $variationAttrValues) ? 'border-vendor-500 bg-vendor-50' : '' }}">
                                        <input type="radio" name="attribute_values[{{ $attribute->id }}]" value="{{ $value->id }}" 
                                               {{ in_array($value->id, $variationAttrValues) ? 'checked' : '' }}
                                               class="rounded-full text-vendor-600 mr-2">
                                        @if($attribute->type === 'color' && $value->color_code)
                                            <span class="w-5 h-5 rounded-full border mr-2" style="background-color: {{ $value->color_code }}"></span>
                                        @endif
                                        <span>{{ $value->value }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Variation Image -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Variation Image</h3>
                
                @if($variation->image)
                <div class="mb-4">
                    <img src="{{ asset('storage/' . $variation->image) }}" class="w-32 h-32 object-cover rounded-lg">
                    <label class="flex items-center mt-2">
                        <input type="checkbox" name="remove_image" value="1" class="rounded text-red-600">
                        <span class="ml-2 text-sm text-red-600">Remove current image</span>
                    </label>
                </div>
                @endif
                
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <input type="file" name="image" accept="image/*" class="hidden" id="variationImage"
                           onchange="previewVariationImage(this)">
                    <label for="variationImage" class="cursor-pointer">
                        <i class="fas fa-image text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-600 text-sm">{{ $variation->image ? 'Replace image' : 'Upload variation image' }}</p>
                    </label>
                </div>
                <div id="imagePreview" class="mt-4"></div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Submit -->
            <div class="bg-white rounded-xl shadow-sm p-6 sticky top-4">
                <button type="submit" class="w-full py-3 bg-vendor-600 text-white font-semibold rounded-lg hover:bg-vendor-700 transition">
                    <i class="fas fa-save mr-2"></i>Update Variation
                </button>
                <a href="{{ route('vendor.products.edit', $product) }}" class="block w-full py-3 mt-2 text-center border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </a>
            </div>

            <!-- Status -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Status</h3>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $variation->is_active) ? 'checked' : '' }} class="rounded text-vendor-600">
                    <span class="ml-2">Active</span>
                </label>
                <p class="text-xs text-gray-500 mt-2">Inactive variations won't be available for purchase</p>
            </div>

            <!-- Product Info -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Product</h3>
                <div class="flex items-center space-x-3">
                    @if($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" class="w-12 h-12 object-cover rounded-lg">
                    @endif
                    <div>
                        <p class="font-medium">{{ $product->name }}</p>
                        <p class="text-sm text-gray-500">{{ $product->sku }}</p>
                    </div>
                </div>
            </div>

            <!-- Current Values Summary -->
            @if($variation->attributeValues && $variation->attributeValues->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Current Attributes</h3>
                <div class="space-y-2">
                    @foreach($variation->attributeValues as $attrValue)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">{{ $attrValue->attribute->name }}:</span>
                        <span class="font-medium">
                            @if($attrValue->attribute->type === 'color' && $attrValue->color_code)
                                <span class="inline-flex items-center">
                                    <span class="w-4 h-4 rounded-full mr-1" style="background-color: {{ $attrValue->color_code }}"></span>
                                    {{ $attrValue->value }}
                                </span>
                            @else
                                {{ $attrValue->value }}
                            @endif
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function previewVariationImage(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="w-32 h-32 object-cover rounded-lg">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush


