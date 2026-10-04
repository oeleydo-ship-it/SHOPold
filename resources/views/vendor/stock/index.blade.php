@extends('layouts.vendor')

@section('title', 'Stock Management')
@section('page-title', 'Stock Management')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Stock Management</h1>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-boxes text-blue-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Products</p>
                <p class="text-2xl font-bold text-gray-900">{{ $statistics['total'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-check-circle text-green-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">In Stock</p>
                <p class="text-2xl font-bold text-green-600">{{ $statistics['in_stock'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Low Stock</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $statistics['low_stock'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Out of Stock</p>
                <p class="text-2xl font-bold text-red-600">{{ $statistics['out_of_stock'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="{{ route('vendor.stock.index') }}" method="GET" class="flex flex-wrap gap-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or SKU..." class="px-4 py-2 border rounded-lg flex-1 min-w-[200px]">
        <select name="status" class="px-4 py-2 border rounded-lg">
            <option value="">All Status</option>
            <option value="in_stock" {{ request('status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
            <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
            <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
        </select>
        <select name="category_id" class="px-4 py-2 border rounded-lg">
            <option value="">All Categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-vendor-600 text-white rounded-lg hover:bg-vendor-700">Filter</button>
        @if(request()->hasAny(['search', 'status', 'category_id']))
            <a href="{{ route('vendor.stock.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Clear</a>
        @endif
    </form>
</div>

<!-- Stock Table -->
<div x-data="vendorStockManager()" x-cloak>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($stockItems as $item)
                <tr class="{{ $item->is_variation ? 'bg-gray-50' : '' }}">
                    <td class="px-4 py-4">
                        <div class="flex items-center">
                            @if($item->is_variation)
                                <div class="w-8 h-8 mr-3"></div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-level-up-alt fa-rotate-90 text-gray-400 mr-1"></i>
                                        {{ $item->name }}
                                    </p>
                                </div>
                            @else
                                <div class="w-10 h-10 bg-gray-100 rounded-lg mr-3 overflow-hidden flex-shrink-0">
                                    @if($item->image_url)
                                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium">{{ $item->name }}</p>
                                    @if($item->product_type === 'variable')
                                        <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 text-xs rounded">Variable</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-4 text-gray-500 text-sm">{{ $item->sku ?: '-' }}</td>
                    <td class="px-4 py-4">
                        <div x-data="{ editing: false, quantity: {{ $item->quantity }}, saving: false }" class="inline-flex items-center">
                            <span x-show="!editing" 
                                  @click="editing = true" 
                                  class="cursor-pointer px-2 py-1 rounded hover:bg-gray-100 {{ $item->stock_status === 'out_of_stock' ? 'text-red-600 font-medium' : ($item->stock_status === 'low_stock' ? 'text-yellow-600 font-medium' : '') }}">
                                {{ $item->quantity }}
                                <i class="fas fa-pencil-alt text-gray-400 text-xs ml-1"></i>
                            </span>
                            <div x-show="editing" class="flex items-center gap-1">
                                <input type="number" 
                                       x-model="quantity" 
                                       min="0" 
                                       @keydown.enter="saveStock({{ $item->id }}, '{{ $item->type }}', quantity, () => { editing = false })"
                                       @keydown.escape="editing = false"
                                       class="w-20 px-2 py-1 border rounded text-sm">
                                <button @click="saveStock({{ $item->id }}, '{{ $item->type }}', quantity, () => { editing = false })" 
                                        :disabled="saving"
                                        class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button @click="editing = false; quantity = {{ $item->quantity }}" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        @if($item->stock_status === 'in_stock')
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">In Stock</span>
                        @elseif($item->stock_status === 'low_stock')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Low Stock</span>
                        @else
                            <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Out of Stock</span>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-gray-500 text-sm">{{ $item->category_name ?: '-' }}</td>
                    <td class="px-4 py-4">
                        <button @click="showHistory({{ $item->product_id }})" 
                                class="text-gray-500 hover:text-gray-700" 
                                title="View History">
                            <i class="fas fa-history"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-box-open text-4xl mb-2"></i>
                        <p>No products found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $stockItems->links('vendor.pagination.tailwind') }}</div>
    </div>


    <!-- History Modal -->
    <div x-show="historyModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="historyModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="historyModal = false"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="historyModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900" id="modal-title">
                            <i class="fas fa-history mr-2"></i>
                            Inventory History: <span x-text="historyProductName"></span>
                        </h3>
                        <button @click="historyModal = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div x-show="historyLoading" class="py-8 text-center">
                        <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                        <p class="mt-2 text-gray-500">Loading history...</p>
                    </div>
                    
                    <div x-show="!historyLoading && historyLogs.length === 0" class="py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>No history records found</p>
                    </div>
                    
                    <div x-show="!historyLoading && historyLogs.length > 0" class="max-h-96 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Before</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Change</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">After</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <template x-for="log in historyLogs" :key="log.id">
                                    <tr>
                                        <td class="px-3 py-2 text-gray-500" x-text="formatDate(log.created_at)"></td>
                                        <td class="px-3 py-2" x-text="log.user?.name || 'System'"></td>
                                        <td class="px-3 py-2">
                                            <span :class="{
                                                'bg-green-100 text-green-700': log.type === 'add',
                                                'bg-red-100 text-red-700': log.type === 'remove' || log.type === 'sale',
                                                'bg-blue-100 text-blue-700': log.type === 'adjust'
                                            }" class="px-2 py-0.5 rounded text-xs" x-text="log.type"></span>
                                        </td>
                                        <td class="px-3 py-2 text-right" x-text="log.quantity_before"></td>
                                        <td class="px-3 py-2 text-right">
                                            <span :class="log.quantity_change >= 0 ? 'text-green-600' : 'text-red-600'">
                                                <span x-text="log.quantity_change >= 0 ? '+' : ''"></span><span x-text="log.quantity_change"></span>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right font-medium" x-text="log.quantity_after"></td>
                                        <td class="px-3 py-2 text-gray-500 truncate max-w-[150px]" x-text="log.note || '-'" :title="log.note"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="historyModal = false" type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function vendorStockManager() {
    return {
        historyModal: false,
        historyLoading: false,
        historyProductName: '',
        historyLogs: [],

        async saveStock(itemId, itemType, quantity, callback) {
            try {
                const response = await fetch('{{ route("vendor.stock.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        item_type: itemType,
                        quantity: parseInt(quantity)
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    if (callback) callback();
                    window.location.reload();
                } else {
                    alert(data.message || 'An error occurred');
                }
            } catch (error) {
                alert('An error occurred while updating stock');
            }
        },

        async showHistory(productId) {
            this.historyModal = true;
            this.historyLoading = true;
            this.historyLogs = [];
            
            try {
                const response = await fetch(`{{ url('vendor/stock/history') }}/${productId}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                this.historyProductName = data.product.name;
                this.historyLogs = data.logs.data || data.logs;
            } catch (error) {
                alert('Failed to load history');
            } finally {
                this.historyLoading = false;
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>
@endpush
