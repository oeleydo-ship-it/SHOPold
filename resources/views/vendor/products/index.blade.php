@extends('layouts.vendor')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')
<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">My Products</h1>
        <p class="text-gray-500">Manage your product catalog</p>
    </div>
    <a href="{{ route('vendor.products.create') }}" class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 bg-vendor-600 text-white rounded-lg hover:bg-vendor-700 transition">
        <i class="fas fa-plus mr-2"></i>Add New Product
    </a>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="" method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." 
                   class="w-full px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
        </div>
        <select name="status" class="px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
            <option value="">All Status</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <select name="type" class="px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
            <option value="">All Types</option>
            <option value="simple" {{ request('type') == 'simple' ? 'selected' : '' }}>Simple</option>
            <option value="variable" {{ request('type') == 'variable' ? 'selected' : '' }}>Variable</option>
        </select>
        <select name="category" class="px-4 py-2 border rounded-lg focus:ring-vendor-500 focus:border-vendor-500">
            <option value="">All Categories</option>
            @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
            @endforeach
        </select>
        <button type="submit" class="px-6 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
            <i class="fas fa-search mr-2"></i>Filter
        </button>
    </form>
</div>

<!-- Products Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($products as $product)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" 
                                     alt="{{ $product->name }}" 
                                     class="w-full h-full object-cover">
                                @elseif($product->images && $product->images->first())
                                <img src="{{ asset('storage/' . $product->images->first()->image) }}" 
                                     alt="{{ $product->name }}" 
                                     class="w-full h-full object-cover">
                                @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                @endif
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">{{ Str::limit($product->name, 40) }}</p>
                                <p class="text-xs text-gray-500">SKU: {{ $product->sku }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($product->product_type === 'variable')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                            <i class="fas fa-layer-group mr-1"></i>Variable
                            @if($product->variations_count > 0)
                            <span class="ml-1">({{ $product->variations_count }})</span>
                            @endif
                        </span>
                        @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                            <i class="fas fa-cube mr-1"></i>Simple
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $product->category->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4">
                        <span class="font-medium">{{ format_currency($product->price) }}</span>
                        @if($product->sale_price)
                        <span class="text-xs text-gray-400 line-through ml-1">{{ format_currency($product->sale_price) }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($product->product_type === 'variable')
                            @php
                                $totalStock = $product->variations->sum('quantity');
                            @endphp
                            @if($totalStock <= 0)
                            <span class="text-red-600 font-medium">Out of Stock</span>
                            @else
                            <span class="text-gray-600">{{ $totalStock }} (vars)</span>
                            @endif
                        @else
                            @if($product->quantity <= 0)
                            <span class="text-red-600 font-medium">Out of Stock</span>
                            @elseif($product->quantity <= ($product->low_stock_threshold ?? 5))
                            <span class="text-amber-600 font-medium">{{ $product->quantity }} (Low)</span>
                            @else
                            <span class="text-gray-600">{{ $product->quantity }}</span>
                            @endif
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($product->is_active)
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                        @else
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('shop.show', $product->slug) }}" target="_blank" 
                               class="p-2 text-gray-500 hover:text-gray-700" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('vendor.products.edit', $product) }}" 
                               class="p-2 text-blue-500 hover:text-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('vendor.products.toggle', $product) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="p-2 {{ $product->is_active ? 'text-amber-500 hover:text-amber-700' : 'text-green-500 hover:text-green-700' }}" 
                                        title="{{ $product->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="fas {{ $product->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('vendor.products.destroy', $product) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Are you sure you want to delete this product?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-red-500 hover:text-red-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-box-open text-5xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 mb-4">No products yet</p>
                            <a href="{{ route('vendor.products.create') }}" class="px-4 py-2 bg-vendor-600 text-white rounded-lg hover:bg-vendor-700 transition">
                                <i class="fas fa-plus mr-2"></i>Add Your First Product
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($products->hasPages())
    <div class="px-6 py-4 border-t">
        {{ $products->links() }}
    </div>
    @endif
</div>
@endsection
