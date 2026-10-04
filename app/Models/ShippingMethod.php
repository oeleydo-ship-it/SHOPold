<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    protected $fillable = [
        'shipping_zone_id',
        'name',
        'type',
        'cost',
        'min_order_amount',
        'per_kg_rate',
        'per_item_rate',
        'delivery_time',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'per_kg_rate' => 'decimal:2',
        'per_item_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    /**
     * Calculate shipping cost based on method type
     */
    public function calculateCost(float $orderTotal = 0, float $orderWeight = 0, int $itemCount = 1): float
    {
        switch ($this->type) {
            case 'free':
                // Free shipping if order meets minimum
                if ($this->min_order_amount && $orderTotal < $this->min_order_amount) {
                    return 999999; // Return high value to indicate not available
                }
                return 0;

            case 'flat_rate':
                return (float) $this->cost;

            case 'weight_based':
                $baseCost = (float) $this->cost;
                $weightCost = $orderWeight * (float) ($this->per_kg_rate ?? 0);
                return $baseCost + $weightCost;

            case 'price_based':
                // Could implement tiered pricing based on order total
                return (float) $this->cost;

            case 'per_item':
                return (float) $this->cost + ($itemCount * (float) ($this->per_item_rate ?? 0));

            default:
                return (float) $this->cost;
        }
    }

    /**
     * Check if this method is available for the given order
     */
    public function isAvailableFor(float $orderTotal = 0): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->type === 'free' && $this->min_order_amount) {
            return $orderTotal >= $this->min_order_amount;
        }

        return true;
    }
}
