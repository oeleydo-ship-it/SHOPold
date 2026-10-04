@extends('layouts.admin')

@section('title', 'Edit Variation')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-2"></i>Back to {{ $product->name }}
    </a>
</div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Edit Variation</h1>
        <p class="text-gray-500">{{ $product->name }} - {{ $variation->variation_name ?: 'Variation #' . $variation->id }}</p>
    </div>
</div>

<form action="{{ route('admin.products.variations.update', [$product->id, $variation->id]) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')
    
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-4">Variation Details</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                        <input type="text" name="sku" value="{{ old('sku', $variation->sku) }}" required class="w-full px-4 py-2 border rounded-lg">
                        @error('sku')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
                        <input type="text" name="barcode" value="{{ old('barcode', $variation->barcode) }}" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-4">Pricing</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Price</label>
                        <input type="number" name="price" value="{{ old('price', $variation->price) }}" step="0.01" min="0" class="w-full px-4 py-2 border rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">Leave empty to use parent price ({{ format_currency($product->price) }})</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sale Price</label>
                        <input type="number" name="sale_price" value="{{ old('sale_price', $variation->sale_price) }}" step="0.01" min="0" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
            </div>

            <!-- Inventory -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-4">Inventory</h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                        <input type="number" name="quantity" value="{{ old('quantity', $variation->quantity) }}" min="0" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Weight (kg)</label>
                        <input type="number" name="weight" value="{{ old('weight', $variation->weight) }}" step="0.01" min="0" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $variation->is_active) ? 'checked' : '' }} class="rounded text-blue-600">
                            <span class="ml-2">Active</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Attributes -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-4">Attribute Values</h3>
                <p class="text-sm text-gray-500 mb-4">Select the attribute values that define this variation</p>
                
                @php
                    $variationAttrValues = $variation->attributeValues->pluck('id')->toArray();
                @endphp
                
                <div class="space-y-4">
                    @foreach($attributes as $attribute)
                    <div class="border rounded-lg p-4">
                        <label class="font-medium mb-2 block">{{ $attribute->name }}</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($attribute->values as $value)
                                <label class="inline-flex items-center px-3 py-1.5 border rounded-lg cursor-pointer hover:bg-gray-50 transition {{ in_array($value->id, $variationAttrValues) ? 'bg-blue-50 border-blue-300' : '' }}">
                                    <input type="radio" name="attribute_values[{{ $attribute->id }}]" value="{{ $value->id }}" 
                                           {{ in_array($value->id, $variationAttrValues) ? 'checked' : '' }}
                                           class="text-blue-600 mr-2">
                                    @if($attribute->type === 'color' && $value->color_code)
                                        <span class="w-4 h-4 rounded-full border mr-2" style="background-color: {{ $value->color_code }}"></span>
                                    @endif
                                    <span class="text-sm">{{ $value->value }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-6">
            <!-- Variation Image -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-4">Variation Image</h3>
                @include('components.media-picker', [
                    'name' => 'image',
                    'value' => $variation->image,
                    'collection' => 'products',
                    'label' => $variation->image ? 'Change Image' : 'Select Image'
                ])
                <p class="text-xs text-gray-500 mt-2">Optional. Uses parent product image if not set.</p>
            </div>

            <!-- Current Values -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold mb-4">Current Values</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Current Price:</span>
                        <span class="font-medium">{{ format_currency($variation->current_price) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Stock:</span>
                        <span class="font-medium {{ $variation->quantity > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $variation->quantity }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status:</span>
                        <span class="px-2 py-0.5 rounded text-xs {{ $variation->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $variation->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                Update Variation
            </button>
        </div>
    </div>
</form>

<!-- Delete form OUTSIDE the main form to prevent nesting issues -->
<div class="lg:grid lg:grid-cols-3 gap-6 mt-6">
    <div class="lg:col-start-3">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-red-600 mb-2">Delete Variation</h3>
            <form action="{{ route('admin.products.variations.destroy', [$product->id, $variation->id]) }}" method="POST" onsubmit="return confirm('Delete this variation?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full px-4 py-2 border border-red-500 text-red-500 rounded-lg hover:bg-red-50">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
