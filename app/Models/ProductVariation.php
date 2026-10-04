<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'sale_price',
        'quantity',
        'image',
        'weight',
        'dimensions',
        'is_active',
        'manage_stock',
        'stock_status',
        'low_stock_threshold',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'manage_stock' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'variation_attribute_value', 'product_variation_id', 'attribute_value_id')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }

    /**
     * Sync attribute values with their attribute IDs
     */
    public function syncAttributeValues(array $valueIds)
    {
        $syncData = [];
        foreach ($valueIds as $valueId) {
            $value = AttributeValue::find($valueId);
            if ($value) {
                $syncData[$valueId] = ['attribute_id' => $value->attribute_id];
            }
        }
        return $this->attributeValues()->sync($syncData);
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock')->where('quantity', '>', 0);
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return $this->product->image_url;
    }

    public function getCurrentPriceAttribute()
    {
        if ($this->sale_price) {
            return $this->sale_price;
        }
        if ($this->price) {
            return $this->price;
        }
        return $this->product->current_price;
    }

    public function getDisplayPriceAttribute()
    {
        return $this->price ?? $this->product->price;
    }

    public function getDisplaySalePriceAttribute()
    {
        return $this->sale_price ?? $this->product->sale_price;
    }

    public function isInStock(): bool
    {
        if (!$this->manage_stock) {
            return $this->stock_status === 'in_stock';
        }
        return $this->quantity > 0;
    }

    public function isLowStock(): bool
    {
        $threshold = $this->low_stock_threshold ?? 5;
        return $this->quantity > 0 && $this->quantity <= $threshold;
    }

    /**
     * Get variation name from attribute values
     */
    public function getVariationNameAttribute()
    {
        if ($this->name) {
            return $this->name;
        }
        
        return $this->attributeValues->pluck('value')->implode(' / ');
    }

    /**
     * Get full name including product name
     */
    public function getFullNameAttribute()
    {
        return $this->product->name . ' - ' . $this->variation_name;
    }

    /**
     * Update variation name based on attribute values
     */
    public function updateVariationName()
    {
        $name = $this->attributeValues()->with('attribute')->get()
            ->map(function ($value) {
                return $value->value;
            })
            ->implode(' / ');
        
        if ($name) {
            $this->update(['name' => $name]);
        }
    }
}
