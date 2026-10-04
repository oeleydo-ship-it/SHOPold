<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\InventoryLog;
use App\Models\Category;
use App\Models\Vendor;
use App\Services\StockService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Display stock management index with filtering and pagination
     */
    public function index(Request $request)
    {
        $threshold = (int) setting('products.low_stock_threshold', 5);
        
        // Build stock items query
        $stockItems = $this->buildStockQuery($request, $threshold);
        
        // Get statistics
        $statistics = $this->stockService->getStatistics();
        
        // Get filter options
        $categories = Category::orderBy('name')->get();
        $vendors = Vendor::orderBy('store_name')->get();
        
        return Inertia::render('Admin/Stock/Index', [
            'items' => $stockItems->through(fn ($i) => [
                'key' => $i->type . '-' . $i->id,
                'id' => $i->id,
                'type' => $i->type,
                'product_id' => $i->product_id,
                'name' => $i->name,
                'sku' => $i->sku,
                'quantity' => $i->quantity,
                'status' => $i->stock_status,
                'vendor' => $i->vendor_name,
                'category' => $i->category_name,
                'image' => $i->is_variation ? null : $i->image_url,
                'is_variation' => $i->is_variation,
                'product_type' => $i->product_type,
            ]),
            'statistics' => $statistics,
            'categories' => $categories->map->only(['id', 'name'])->values(),
            'vendors' => $vendors->map(fn ($v) => ['id' => $v->id, 'store_name' => $v->store_name])->values(),
            'filters' => $request->only('search', 'status', 'vendor_id', 'category_id'),
            'threshold' => $threshold,
        ]);
    }

    /**
     * Update stock for a single product or variation
     */
    public function update(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'item_type' => 'required|in:product,variation',
            'quantity' => 'required|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $item = $this->findItem($request->item_type, $request->item_id);
        
        if (!$item) {
            return back()->with('error', 'Item not found.');
        }

        $this->stockService->updateStock(
            $item,
            $request->quantity,
            'adjust',
            $request->note ?? 'Manual stock adjustment'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully.',
                'quantity' => $request->quantity,
            ]);
        }

        return back()->with('success', 'Stock updated successfully.');
    }


    /**
     * Perform bulk stock updates
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'action' => 'required|in:set,add,reduce',
            'quantity' => 'required|integer|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.type' => 'required|in:product,variation',
            'note' => 'nullable|string|max:255',
        ]);

        $items = collect();
        foreach ($request->items as $itemData) {
            $item = $this->findItem($itemData['type'], $itemData['id']);
            if ($item) {
                $items->push($item);
            }
        }

        if ($items->isEmpty()) {
            return back()->with('error', 'No valid items found for update.');
        }

        $note = $request->note ?? 'Bulk stock ' . $request->action;

        switch ($request->action) {
            case 'set':
                $this->stockService->bulkSetStock($items, $request->quantity, $note);
                break;
            case 'add':
                $this->stockService->bulkAddStock($items, $request->quantity, $note);
                break;
            case 'reduce':
                $this->stockService->bulkReduceStock($items, $request->quantity, $note);
                break;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bulk update completed successfully.',
                'updated_count' => $items->count(),
            ]);
        }

        return back()->with('success', "Stock updated for {$items->count()} items.");
    }

    /**
     * Display inventory history for a product
     */
    public function history(Product $product)
    {
        $logs = InventoryLog::where('product_id', $product->id)
            ->with(['user', 'variation'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if (request()->wantsJson()) {
            return response()->json([
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                ],
                'logs' => $logs,
            ]);
        }

        return Inertia::render('Admin/Stock/History', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $product->quantity,
                'image' => $product->image ? $product->image_url : null,
            ],
            'logs' => $logs->through(fn ($l) => [
                'id' => $l->id,
                'date' => $l->created_at->format('M d, Y H:i'),
                'user' => $l->user?->name ?? 'System',
                'type' => $l->type,
                'variation' => $l->variation ? ($l->variation->name ?: $l->variation->sku) : null,
                'before' => $l->quantity_before,
                'change' => $l->quantity_change,
                'after' => $l->quantity_after,
                'note' => $l->note,
            ]),
        ]);
    }

    /**
     * Export stock data as CSV
     */
    public function export(Request $request): StreamedResponse
    {
        $threshold = (int) setting('products.low_stock_threshold', 5);
        
        // Get all items matching current filters (no pagination)
        $items = $this->buildStockQueryForExport($request, $threshold);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock-export-' . date('Y-m-d') . '.csv"',
        ];

        return new StreamedResponse(function () use ($items) {
            $handle = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($handle, [
                'Product Name',
                'SKU',
                'Quantity',
                'Status',
                'Vendor',
                'Category',
            ]);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->display_name,
                    $item->sku,
                    $item->quantity,
                    $item->stock_status_label,
                    $item->vendor_name ?? 'Admin',
                    $item->category_name ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }


    /**
     * Build the stock query with filters
     */
    protected function buildStockQuery(Request $request, int $threshold)
    {
        $perPage = $request->input('per_page', 25);
        
        // Get products with their variations
        $productsQuery = Product::query()
            ->with(['category', 'vendor', 'variations'])
            ->select([
                'id',
                'name',
                'sku',
                'quantity',
                'category_id',
                'vendor_id',
                'image',
                'product_type',
            ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Apply vendor filter
        if ($request->filled('vendor_id')) {
            if ($request->vendor_id === 'admin') {
                $productsQuery->whereNull('vendor_id');
            } else {
                $productsQuery->where('vendor_id', $request->vendor_id);
            }
        }

        // Apply category filter
        if ($request->filled('category_id')) {
            $productsQuery->where('category_id', $request->category_id);
        }

        // Apply status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'in_stock':
                    $productsQuery->where('quantity', '>', $threshold);
                    break;
                case 'low_stock':
                    $productsQuery->where('quantity', '>', 0)
                                  ->where('quantity', '<=', $threshold);
                    break;
                case 'out_of_stock':
                    $productsQuery->where('quantity', '<=', 0);
                    break;
            }
        }

        $products = $productsQuery->orderBy('name')->paginate($perPage)->withQueryString();

        // Transform to stock items (flatten products and variations)
        $stockItems = collect();
        
        foreach ($products as $product) {
            // Add product itself (for simple products or as parent)
            $stockItems->push($this->transformToStockItem($product, $threshold));
            
            // Add variations for variable products
            if ($product->product_type === 'variable') {
                foreach ($product->variations as $variation) {
                    $stockItems->push($this->transformVariationToStockItem($variation, $product, $threshold));
                }
            }
        }

        // Return paginated result with transformed items
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $stockItems,
            $products->total(),
            $products->perPage(),
            $products->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * Build stock query for export (no pagination)
     */
    protected function buildStockQueryForExport(Request $request, int $threshold)
    {
        $productsQuery = Product::query()
            ->with(['category', 'vendor', 'variations'])
            ->select([
                'id',
                'name',
                'sku',
                'quantity',
                'category_id',
                'vendor_id',
                'product_type',
            ]);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('vendor_id')) {
            if ($request->vendor_id === 'admin') {
                $productsQuery->whereNull('vendor_id');
            } else {
                $productsQuery->where('vendor_id', $request->vendor_id);
            }
        }

        if ($request->filled('category_id')) {
            $productsQuery->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'in_stock':
                    $productsQuery->where('quantity', '>', $threshold);
                    break;
                case 'low_stock':
                    $productsQuery->where('quantity', '>', 0)
                                  ->where('quantity', '<=', $threshold);
                    break;
                case 'out_of_stock':
                    $productsQuery->where('quantity', '<=', 0);
                    break;
            }
        }

        $products = $productsQuery->orderBy('name')->get();

        // Transform to stock items
        $stockItems = collect();
        
        foreach ($products as $product) {
            $stockItems->push($this->transformToStockItem($product, $threshold));
            
            if ($product->product_type === 'variable') {
                foreach ($product->variations as $variation) {
                    $stockItems->push($this->transformVariationToStockItem($variation, $product, $threshold));
                }
            }
        }

        return $stockItems;
    }


    /**
     * Transform a product to a stock item object
     */
    protected function transformToStockItem(Product $product, int $threshold): object
    {
        $status = $this->getStockStatus($product->quantity, $threshold);
        
        return (object) [
            'id' => $product->id,
            'type' => 'product',
            'product_id' => $product->id,
            'display_name' => $product->name,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => $product->quantity,
            'stock_status' => $status,
            'stock_status_label' => $this->getStockStatusLabel($status),
            'vendor_id' => $product->vendor_id,
            'vendor_name' => $product->vendor?->store_name,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'image_url' => $product->image_url,
            'is_variation' => false,
            'product_type' => $product->product_type,
        ];
    }

    /**
     * Transform a variation to a stock item object
     */
    protected function transformVariationToStockItem(ProductVariation $variation, Product $product, int $threshold): object
    {
        $status = $this->getStockStatus($variation->quantity, $threshold);
        
        return (object) [
            'id' => $variation->id,
            'type' => 'variation',
            'product_id' => $product->id,
            'display_name' => $product->name . ' - ' . ($variation->name ?: $variation->sku),
            'name' => $variation->name ?: $variation->sku,
            'sku' => $variation->sku,
            'quantity' => $variation->quantity,
            'stock_status' => $status,
            'stock_status_label' => $this->getStockStatusLabel($status),
            'vendor_id' => $product->vendor_id,
            'vendor_name' => $product->vendor?->store_name,
            'category_id' => $product->category_id,
            'category_name' => $product->category?->name,
            'image_url' => $variation->image_url,
            'is_variation' => true,
            'product_type' => 'variation',
        ];
    }

    /**
     * Get stock status based on quantity and threshold
     */
    protected function getStockStatus(int $quantity, int $threshold): string
    {
        if ($quantity <= 0) {
            return 'out_of_stock';
        }
        if ($quantity <= $threshold) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    /**
     * Get human-readable stock status label
     */
    protected function getStockStatusLabel(string $status): string
    {
        return match ($status) {
            'in_stock' => 'In Stock',
            'low_stock' => 'Low Stock',
            'out_of_stock' => 'Out of Stock',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Find a product or variation by type and ID
     */
    protected function findItem(string $type, int $id): Product|ProductVariation|null
    {
        if ($type === 'product') {
            return Product::find($id);
        }
        
        return ProductVariation::find($id);
    }
}
