<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Product data for storefront cards (grids, sliders, list view). Load products with ProductCards::with($query)
 * so ratings, stock and translations come in a few queries instead of several per card.
 */
class ProductCards
{
    /** Eager loads and aggregates a card needs. */
    public static function with(Builder $query): Builder
    {
        return $query
            ->with(['category.translations', 'translations', 'activeVariations:id,product_id,quantity,is_active', 'images:id,product_id,image,is_primary,sort_order'])
            ->withCount(['approvedReviews as card_review_count'])
            ->withAvg('approvedReviews as card_average_rating', 'rating');
    }

    public static function map(iterable $products): array
    {
        return Collection::make($products)->map(fn (Product $p) => static::card($p))->values()->all();
    }

    public static function card(Product $p): array
    {
        $onSale = $p->isOnSale();
        // Aggregates from ProductCards::with() (present but null when there are no reviews); accessors otherwise.
        $attrs = $p->getAttributes();
        $reviews = (int) (array_key_exists('card_review_count', $attrs) ? $attrs['card_review_count'] : $p->review_count);
        $rating = round((float) (array_key_exists('card_average_rating', $attrs) ? $attrs['card_average_rating'] : $p->average_rating), 1);

        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'url' => route('shop.show', $p->slug),
            'name' => $p->translated_name,
            'short' => $p->translated_short_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $p->description), 120),
            // resized WebP copy for the card (ImageController)
            'image' => $p->image ? \App\Http\Controllers\ImageController::url($p->image, 480) : null,
            'imageAny' => static::anyImage($p),
            'price' => format_currency($onSale ? $p->sale_price : $p->price),
            'regular' => $onSale ? format_currency($p->price) : null,
            'discount' => $onSale ? (int) $p->discount_percent : 0,
            'isNew' => $p->created_at && $p->created_at->gt(now()->subDays(14)),
            'featured' => (bool) $p->is_featured,
            'category' => $p->category ? [
                'name' => $p->category->translated_name ?? $p->category->name,
                'slug' => $p->category->slug,
                'url' => route('shop.category', $p->category->slug),
            ] : null,
            'reviews' => $reviews,
            'rating' => $rating,
            'inStock' => static::inStock($p),
            'variable' => $p->product_type === 'variable',
        ];
    }

    /** Main image, else the gallery's primary/first image (Product::image_url without a query per product when images are loaded). */
    protected static function anyImage(Product $p): string
    {
        if ($p->image) {
            return \App\Http\Controllers\ImageController::url($p->image, 480);
        }
        if (!$p->relationLoaded('images')) {
            return $p->image_url;
        }
        $img = $p->images->firstWhere('is_primary', true) ?? $p->images->sortBy('sort_order')->first();

        return $img ? \App\Http\Controllers\ImageController::url($img->image, 480) : asset('images/no-image.png');
    }

    /** Same rules as Product::isInStock(), using the eager-loaded variations when present. */
    protected static function inStock(Product $p): bool
    {
        if ($p->product_type === 'variable' && $p->relationLoaded('activeVariations')) {
            return $p->activeVariations->contains(fn ($v) => $v->quantity > 0);
        }

        return $p->isInStock();
    }
}
