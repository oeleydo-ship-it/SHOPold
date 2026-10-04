<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $fillable = [
        'name',
        'countries',
        'regions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'countries' => 'array',
        'regions' => 'array',
        'is_active' => 'boolean',
    ];

    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class)->orderBy('sort_order');
    }

    public function activeMethods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class)->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Find the shipping zone for a given country code
     */
    public static function findForCountry(string $countryCode): ?self
    {
        return static::where('is_active', true)
            ->get()
            ->first(function ($zone) use ($countryCode) {
                $countries = $zone->countries ?? [];
                return in_array($countryCode, $countries) || in_array('*', $countries);
            });
    }

    /**
     * Get available shipping methods for a country (returns Collection of ShippingMethod models)
     */
    public static function getMethodsForCountry(string $countryCode)
    {
        $zone = static::findForCountry($countryCode);
        
        if (!$zone) {
            return collect([]);
        }

        return $zone->activeMethods;
    }
    
    /**
     * Get available shipping methods for a country as array
     */
    public static function getMethodsArrayForCountry(string $countryCode, float $orderTotal = 0, float $orderWeight = 0): array
    {
        $zone = static::findForCountry($countryCode);
        
        if (!$zone) {
            return [];
        }

        return $zone->activeMethods->map(function ($method) use ($orderTotal, $orderWeight) {
            return [
                'id' => $method->id,
                'name' => $method->name,
                'cost' => $method->calculateCost($orderTotal, $orderWeight),
                'delivery_time' => $method->delivery_time,
            ];
        })->toArray();
    }
}
