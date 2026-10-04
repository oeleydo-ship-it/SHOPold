<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Support\Collection;

class StockService
{
    /**
     * Update stock for a single product or variation
     *
     * @param Product|ProductVariation $item
     * @param int $newQuantity
     * @param string $type
     * @param string|null $note
     * @return void
     */
    public function updateStock(Product|ProductVariation $item, int $newQuantity, string $type, ?string $note = null): void
    {
        $oldQuantity = $item->quantity;
        $change = $newQuantity - $oldQuantity;

        $item->update(['quantity' => $newQuantity]);

        $product = $item instanceof Product ? $item : $item->product;
        $variation = $item instanceof ProductVariation ? $item : null;

        InventoryLog::logChange(
            product: $product,
            variation: $variation,
            type: $type,
            quantityChange: $change,
            note: $note,
            quantityBefore: $oldQuantity
        );
    }

    /**
     * Bulk set stock to a specific quantity for multiple items
     *
     * @param Collection $items
     * @param int $quantity
     * @param string|null $note
     * @return void
     */
    public function bulkSetStock(Collection $items, int $quantity, ?string $note = null): void
    {
        foreach ($items as $item) {
            $this->updateStock($item, $quantity, 'adjust', $note);
        }
    }

    /**
     * Bulk add stock to multiple items
     *
     * @param Collection $items
     * @param int $amount
     * @param string|null $note
     * @return void
     */
    public function bulkAddStock(Collection $items, int $amount, ?string $note = null): void
    {
        foreach ($items as $item) {
            $newQuantity = $item->quantity + $amount;
            $this->updateStock($item, $newQuantity, 'add', $note);
        }
    }

    /**
     * Bulk reduce stock from multiple items (cannot go below 0)
     *
     * @param Collection $items
     * @param int $amount
     * @param string|null $note
     * @return void
     */
    public function bulkReduceStock(Collection $items, int $amount, ?string $note = null): void
    {
        foreach ($items as $item) {
            $newQuantity = max(0, $item->quantity - $amount);
            $this->updateStock($item, $newQuantity, 'remove', $note);
        }
    }

    /**
     * Get stock statistics for dashboard
     *
     * @param int|null $vendorId
     * @return array
     */
    public function getStatistics(?int $vendorId = null): array
    {
        $query = Product::query();
        
        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        $threshold = (int) setting('products.low_stock_threshold', 5);

        $total = $query->count();
        $inStock = (clone $query)->where('quantity', '>', $threshold)->count();
        $lowStock = (clone $query)->where('quantity', '>', 0)->where('quantity', '<=', $threshold)->count();
        $outOfStock = (clone $query)->where('quantity', '<=', 0)->count();

        return [
            'total' => $total,
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
        ];
    }
}
