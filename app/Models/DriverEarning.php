<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'order_id',
        'type',
        'amount',
        'description',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getTypeBadgeAttribute()
    {
        return match($this->type) {
            'delivery' => '<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Delivery</span>',
            'tip' => '<span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">Tip</span>',
            'bonus' => '<span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">Bonus</span>',
            'adjustment' => '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Adjustment</span>',
            'payout' => '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Payout</span>',
            default => '<span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Unknown</span>',
        };
    }
}
