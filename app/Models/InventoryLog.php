<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variation_id',
        'user_id',
        'type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'reference_type',
        'reference_id',
        'note',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabel()
    {
        return match($this->type) {
            'add' => 'Stock Added',
            'remove' => 'Stock Removed',
            'adjust' => 'Stock Adjusted',
            'sale' => 'Sold',
            'return' => 'Returned',
            'restock' => 'Restocked',
            'transfer' => 'Transferred',
            default => ucfirst($this->type),
        };
    }

    public function getTypeBadgeClass()
    {
        return match($this->type) {
            'add', 'restock', 'return' => 'bg-green-100 text-green-800',
            'remove', 'sale' => 'bg-red-100 text-red-800',
            'adjust', 'transfer' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Log inventory change
     */
    public static function logChange(
        Product $product,
        ?ProductVariation $variation,
        string $type,
        int $quantityChange,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $note = null,
        ?int $quantityBefore = null
    ): self {
        // If quantityBefore is not provided, read from the target (for backward compatibility)
        if ($quantityBefore === null) {
            $target = $variation ?? $product;
            $quantityBefore = $target->quantity;
        }
        $quantityAfter = $quantityBefore + $quantityChange;

        return static::create([
            'product_id' => $product->id,
            'product_variation_id' => $variation?->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantityChange,
            'quantity_after' => $quantityAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
        ]);
    }
}
