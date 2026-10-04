<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_name',
        'slug',
        'description',
        'logo',
        'banner',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'commission_rate',
        'status',
        'total_sales',
        'total_earnings',
        'balance',
        'social_links',
        'business_info',
        'payment_info',
        'featured',
        'rating_count',
        'average_rating',
        'approved_at',
    ];

    /** Payout details are never serialised (toArray/JSON); read the attribute explicitly where needed. */
    protected $hidden = ['payment_info'];

    protected $casts = [
        'social_links' => 'array',
        'business_info' => 'array',
        'payment_info' => \App\Casts\EncryptedJson::class, // bank / PayPal / Stripe payout details, encrypted at rest
        'featured' => 'boolean',
        'approved_at' => 'datetime',
        'commission_rate' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'balance' => 'decimal:2',
        'average_rating' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function payouts()
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function reviews()
    {
        return $this->hasMany(VendorReview::class);
    }

    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return null;
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Calculate total sales from delivered order items
     */
    public function getCalculatedTotalSalesAttribute()
    {
        $productIds = $this->products()->pluck('id');
        
        return OrderItem::whereIn('product_id', $productIds)
            ->whereHas('order', function($q) {
                $q->whereIn('payment_status', ['paid']);
            })
            ->where('fulfillment_status', 'delivered')
            ->sum('total');
    }

    /**
     * Calculate total earnings (sales minus commission)
     */
    public function getCalculatedTotalEarningsAttribute()
    {
        $totalSales = $this->calculated_total_sales;
        $commission = $totalSales * ($this->commission_rate / 100);
        return $totalSales - $commission;
    }

    /**
     * Calculate available balance (earnings minus paid out)
     */
    public function getCalculatedBalanceAttribute()
    {
        $totalEarnings = $this->calculated_total_earnings;
        $paidOut = $this->payouts()
            ->whereIn('status', ['completed', 'processing'])
            ->sum('amount');
        return $totalEarnings - $paidOut;
    }

    /**
     * Get total amount paid out
     */
    public function getTotalPaidOutAttribute()
    {
        return $this->payouts()
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get pending payout amount
     */
    public function getPendingPayoutAttribute()
    {
        return $this->payouts()
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');
    }

    /**
     * Sync the stored totals with calculated values
     */
    public function syncEarnings()
    {
        $this->update([
            'total_sales' => $this->calculated_total_sales,
            'total_earnings' => $this->calculated_total_earnings,
            'balance' => $this->calculated_balance,
        ]);
    }
}
