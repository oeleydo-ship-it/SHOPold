<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DeliverySlot extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'max_orders',
        'extra_charge',
        'available_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'available_days' => 'array',
        'extra_charge' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTimeRangeAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('g:i A') . ' - ' . 
               Carbon::parse($this->end_time)->format('g:i A');
    }

    public function isAvailableOn(Carbon $date): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $dayOfWeek = $date->dayOfWeek;
        $availableDays = $this->available_days ?? [0, 1, 2, 3, 4, 5, 6];
        
        return in_array($dayOfWeek, $availableDays);
    }

    public function getOrderCountForDate(Carbon $date): int
    {
        return Order::where('delivery_slot_id', $this->id)
            ->whereDate('scheduled_date', $date)
            ->count();
    }

    public function hasCapacityForDate(Carbon $date): bool
    {
        return $this->getOrderCountForDate($date) < $this->max_orders;
    }

    public static function getAvailableSlotsForDate(Carbon $date): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->orderBy('sort_order')
            ->get()
            ->filter(function ($slot) use ($date) {
                return $slot->isAvailableOn($date) && $slot->hasCapacityForDate($date);
            });
    }
}
