<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Product extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'slug',
        'product_type',
        'description',
        'features',
        'short_description',
        'purchase_note',
        'price',
        'sale_price',
        'sale_start_date',
        'sale_end_date',
        'cost_price',
        'sku',
        'barcode',
        'mpn',
        'quantity',
        'manage_stock',
        'stock_status',
        'low_stock_threshold',
        'sold_individually',
        'min_purchase_qty',
        'max_purchase_qty',
        'category_id',
        'brand_id',
        'vendor_id',
        'image',
        'gallery',
        'related_products',
        'upsell_products',
        'cross_sell_products',
        'is_active',
        'is_featured',
        'is_virtual',
        'is_downloadable',
        'external_url',
        'button_text',
        'publish_date',
        'weight',
        'dimensions',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'views',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_virtual' => 'boolean',
        'is_downloadable' => 'boolean',
        'manage_stock' => 'boolean',
        'sold_individually' => 'boolean',
        'gallery' => 'array',
        'features' => 'array',
        'related_products' => 'array',
        'upsell_products' => 'array',
        'cross_sell_products' => 'array',
        'sale_start_date' => 'datetime',
        'sale_end_date' => 'datetime',
        'publish_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            
            // Auto-set flags for digital products
            if ($product->product_type === 'digital') {
                $product->is_downloadable = true;
                $product->is_virtual = true;
            }
        });
        
        static::updating(function ($product) {
            // Auto-set flags for digital products
            if ($product->product_type === 'digital') {
                $product->is_downloadable = true;
                $product->is_virtual = true;
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag')->withTimestamps();
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attribute')
            ->withPivot(['is_visible', 'is_variation'])
            ->withTimestamps();
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_value')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class)->orderBy('sort_order');
    }

    public function activeVariations()
    {
        return $this->hasMany(ProductVariation::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function translations()
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function digitalFiles()
    {
        return $this->hasMany(DigitalFile::class)->orderBy('sort_order');
    }

    public function activeDigitalFiles()
    {
        return $this->hasMany(DigitalFile::class)->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Check if product is a digital product
     */
    public function isDigital(): bool
    {
        return $this->is_downloadable && $this->digitalFiles()->exists();
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function getAllImagesAttribute()
    {
        $images = $this->images->pluck('image_url')->toArray();
        if (empty($images) && $this->image) {
            $images[] = $this->image_url;
        }
        return $images;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function getImageUrlAttribute()
    {
        // First check main image field
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        
        // Fallback to primary ProductImage
        $primaryImage = $this->images()->where('is_primary', true)->first();
        if ($primaryImage) {
            return asset('storage/' . $primaryImage->image);
        }
        
        // Fallback to first ProductImage
        $firstImage = $this->images()->orderBy('sort_order')->first();
        if ($firstImage) {
            return asset('storage/' . $firstImage->image);
        }
        
        return asset('images/no-image.png');
    }

    public function getCurrentPriceAttribute()
    {
        if ($this->isOnSale()) {
            return $this->sale_price;
        }
        return $this->price;
    }

    public function getDiscountPercentAttribute()
    {
        if ($this->isOnSale() && $this->price > 0) {
            return round((($this->price - $this->sale_price) / $this->price) * 100);
        }
        return 0;
    }

    public function getAverageRatingAttribute()
    {
        return $this->approvedReviews()->avg('rating') ?? 0;
    }

    public function getReviewCountAttribute()
    {
        return $this->approvedReviews()->count();
    }

    public function isInStock(): bool
    {
        if ($this->product_type === 'variable') {
            return $this->activeVariations()->where('quantity', '>', 0)->exists();
        }
        
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
     * Check if product is currently on sale
     */
    public function isOnSale(): bool
    {
        if (!$this->sale_price || $this->sale_price >= $this->price) {
            return false;
        }

        $now = Carbon::now();

        // Check start date
        if ($this->sale_start_date && $now->lt($this->sale_start_date)) {
            return false;
        }

        // Check end date
        if ($this->sale_end_date && $now->gt($this->sale_end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Get sale end date for countdown
     */
    public function getSaleEndsInAttribute()
    {
        if (!$this->isOnSale() || !$this->sale_end_date) {
            return null;
        }
        return $this->sale_end_date;
    }

    /**
     * Check if product is variable type
     */
    public function isVariable(): bool
    {
        return $this->product_type === 'variable';
    }

    /**
     * Get price range for variable products
     */
    public function getPriceRangeAttribute()
    {
        if (!$this->isVariable()) {
            return null;
        }

        $variations = $this->activeVariations;
        if ($variations->isEmpty()) {
            return null;
        }

        $prices = $variations->map(function ($v) {
            return $v->current_price;
        })->filter();

        if ($prices->isEmpty()) {
            return null;
        }

        $min = $prices->min();
        $max = $prices->max();

        if ($min === $max) {
            return ['min' => $min, 'max' => $min, 'same' => true];
        }

        return ['min' => $min, 'max' => $max, 'same' => false];
    }

    /**
     * Get total stock across all variations
     */
    public function getTotalStockAttribute()
    {
        if ($this->isVariable()) {
            return $this->variations()->sum('quantity');
        }
        return $this->quantity;
    }

    /**
     * Get profit margin
     */
    public function getProfitMarginAttribute()
    {
        if (!$this->cost_price || $this->cost_price <= 0) {
            return null;
        }
        $sellPrice = $this->current_price;
        return round((($sellPrice - $this->cost_price) / $sellPrice) * 100, 2);
    }

    /**
     * Get SEO title (fallback to product name)
     */
    public function getSeoTitleAttribute()
    {
        return $this->meta_title ?: $this->name;
    }

    /**
     * Get SEO description (fallback to short description)
     */
    public function getSeoDescriptionAttribute()
    {
        return $this->meta_description ?: $this->short_description;
    }

    /**
     * Scope for products on sale
     */
    public function scopeOnSale($query)
    {
        $now = Carbon::now();
        return $query->whereNotNull('sale_price')
            ->where('sale_price', '>', 0)
            ->where(function ($q) use ($now) {
                $q->whereNull('sale_start_date')
                    ->orWhere('sale_start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('sale_end_date')
                    ->orWhere('sale_end_date', '>=', $now);
            });
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query, $threshold = 5)
    {
        return $query->where('quantity', '>', 0)
            ->where('quantity', '<=', $threshold);
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    /**
     * Get related products
     */
    public function getRelatedProductsListAttribute()
    {
        if (empty($this->related_products)) {
            return collect([]);
        }
        return static::whereIn('id', $this->related_products)->active()->get();
    }

    /**
     * Get upsell products
     */
    public function getUpsellProductsListAttribute()
    {
        if (empty($this->upsell_products)) {
            return collect([]);
        }
        return static::whereIn('id', $this->upsell_products)->active()->get();
    }

    /**
     * Get cross-sell products
     */
    public function getCrossSellProductsListAttribute()
    {
        if (empty($this->cross_sell_products)) {
            return collect([]);
        }
        return static::whereIn('id', $this->cross_sell_products)->active()->get();
    }
}
