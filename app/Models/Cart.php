<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function getTotalAttribute()
    {
        return $this->items->sum(function ($item) {
            $unitPrice = $item->variation
                ? $item->variation->current_price
                : $item->product->current_price;
            return $item->quantity * $unitPrice;
        });
    }

    public function getItemCountAttribute()
    {
        return $this->items->sum('quantity');
    }

    public static function getCart()
    {
        if (auth()->check()) {
            $cart = self::firstOrCreate(['user_id' => auth()->id()]);
        } else {
            $sessionId = session()->getId();
            $cart = self::firstOrCreate(['session_id' => $sessionId]);
        }
        return $cart;
    }

    public function addItem($productId, $quantity = 1, $productVariationId = null)
    {
        $itemQuery = $this->items()->where('product_id', $productId);

        if ($productVariationId) {
            $itemQuery->where('product_variation_id', $productVariationId);
        } else {
            $itemQuery->whereNull('product_variation_id');
        }

        $item = $itemQuery->first();
        
        if ($item) {
            $item->increment('quantity', $quantity);
        } else {
            $this->items()->create([
                'product_id' => $productId,
                'product_variation_id' => $productVariationId,
                'quantity' => $quantity,
            ]);
        }
    }

    public function updateItemQuantity($itemId, $quantity)
    {
        $item = $this->items()->find($itemId);
        if ($item) {
            if ($quantity <= 0) {
                $item->delete();
            } else {
                $item->update(['quantity' => $quantity]);
            }
        }
    }

    public function removeItem($itemId)
    {
        $this->items()->where('id', $itemId)->delete();
    }

    public function clear()
    {
        $this->items()->delete();
    }
}
