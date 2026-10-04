<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'order_id',
        'type',
        'title',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getIconAttribute()
    {
        return match($this->type) {
            'new_order' => 'fa-box',
            'order_cancelled' => 'fa-times-circle',
            'rating' => 'fa-star',
            'tip' => 'fa-money-bill',
            'bonus' => 'fa-gift',
            'system' => 'fa-bell',
            default => 'fa-info-circle',
        };
    }

    public function getColorAttribute()
    {
        return match($this->type) {
            'new_order' => 'text-blue-500',
            'order_cancelled' => 'text-red-500',
            'rating' => 'text-yellow-500',
            'tip' => 'text-green-500',
            'bonus' => 'text-purple-500',
            default => 'text-gray-500',
        };
    }
}
