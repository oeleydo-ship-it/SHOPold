<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variation_id',
        'product_name',
        'variation_options',
        'price',
        'quantity',
        'total',
        'fulfillment_status',
        'tracking_number',
        'shipping_carrier',
        'shipped_at',
        'delivered_at',
        'vendor_note',
        'is_digital',
        'downloads_remaining',
        'download_expiry',
        'download_token',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'variation_options' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'is_digital' => 'boolean',
        'downloads_remaining' => 'integer',
        'download_expiry' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    public static function fulfillmentStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function getFulfillmentStatusLabelAttribute(): string
    {
        return self::fulfillmentStatuses()[$this->fulfillment_status] ?? ucfirst($this->fulfillment_status);
    }

    public function getFulfillmentStatusColorAttribute(): string
    {
        return match($this->fulfillment_status) {
            'pending' => 'bg-yellow-100 text-yellow-700',
            'processing' => 'bg-blue-100 text-blue-700',
            'shipped' => 'bg-purple-100 text-purple-700',
            'delivered' => 'bg-green-100 text-green-700',
            'cancelled' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    /**
     * Get formatted variation options for display
     */
    public function getFormattedVariationAttribute()
    {
        if (!$this->variation_options) {
            return null;
        }

        return collect($this->variation_options)
            ->map(fn($value, $key) => "{$key}: {$value}")
            ->implode(', ');
    }

    /**
     * Get download logs for this order item
     */
    public function downloadLogs()
    {
        return $this->hasMany(DownloadLog::class);
    }

    /**
     * Check if downloads are available
     */
    public function canDownload(): bool
    {
        if (!$this->is_digital) {
            return false;
        }

        // Check download limit
        if ($this->downloads_remaining !== null && $this->downloads_remaining <= 0) {
            return false;
        }

        // Check expiry
        if ($this->download_expiry && now()->gt($this->download_expiry)) {
            return false;
        }

        return true;
    }
}
