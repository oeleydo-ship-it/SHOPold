<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\InventoryLog;
use App\Models\Category;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StockController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Display stock management index with filtering and pagination (vendor's products only)
     */
    public function index(Request $request)
    {
        $vendor = Auth::user()->vendor;
        
        if (!$vendor) {
            return redirect()->route('vendor.register');
        }

        $threshold = (int) setting('products.low_stock_threshold', 5);
        
        // Build stock items query (filtered to vendor's products)
        $stockItems = $this->buildStockQuery($request, $threshold, $vendor->id);
        
        // Get statistics for vendor's products only
        $statistics = $this->stockService->getStatistics($vendor->id);
        
        // Get filter options
        $categories = Category::orderBy('name')->get();
        
        return Inertia::render('Vendor/Stock/Index', [
            'items' => $stockItems->through(fn ($i) => [
                'key' => $i->type . '-' . $i->id,
                'id' => $i->id,
                'type' => $i->type,
                'product_id' => $i->product_id,
                'name' => $i->name,
                'sku' => $i->sku,
                'quantity' => (int) $i->quantity,
                'status' => $i->stock_status,
                'category' => $i->category_name,
                'image' => $i->is_variation ? null : $i->image_url,
                'is_variation' => $i->is_variation,
                'product_type' => $i->product_type,
            ]),
            'statistics' => $statistics,
            'categories' => $categories->map->only(['id', 'name'])->values(),
            'filters' => $request->only('search', 'status', 'category_id'),
            'threshold' => $threshold,
        ]);
    }


    /**
     * Update stock for a single product or variation (with vendor ownership verification)
     */
    public function update(Request $request)
    {
        $vendor = Auth::user()->vendor;
        
        if (!$vendor) {
            return back()->with('error', 'Vendor account required.');
        }

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

        // Verify vendor ownership
        if (!$this->verifyVendorOwnership($item, $vendor->id)) {
            abort(403, 'You do not have permission to update this product.');
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
     * Display inventory history for a product (with vendor ownership verification)
     */
    public function history(Product $product)
    {
        $vendor = Auth::user()->vendor;
        
        if (!$vendor) {
            return back()->with('error', 'Vendor account required.');
        }

        // Verify vendor ownership
        if ($product->vendor_id !== $vendor->id) {
            abort(403, 'You do not have permission to view this product history.');
        }

        $logs = InventoryLog::where('product_id', $product->id)
            ->with(['user', 'variation'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Only names leave this page: the log's user may be a store admin, whose account details are not the vendor's business.
        return Inertia::render('Vendor/Stock/History', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => (int) $product->quantity,
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
     * Build the stock query with filters (vendor's products only)
     */
    protected function buildStockQuery(Request $request, int $threshold, int $vendorId)
    {
        $perPage = min(100, max(5, (int) $request->input('per_page', 25)));
        
        // Get products with their variations (filtered to vendor's products)
        $productsQuery = Product::query()
            ->where('vendor_id', $vendorId)
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

    /**
     * Verify that the item belongs to the vendor
     */
    protected function verifyVendorOwnership(Product|ProductVariation $item, int $vendorId): bool
    {
        if ($item instanceof Product) {
            return $item->vendor_id === $vendorId;
        }
        
        // For variations, check the parent product's vendor
        return $item->product && $item->product->vendor_id === $vendorId;
    }
}
