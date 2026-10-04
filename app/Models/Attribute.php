<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_visible',
        'is_variation',
        'is_global',
        'sort_order',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_variation' => 'boolean',
        'is_global' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($attribute) {
            if (empty($attribute->slug)) {
                $attribute->slug = Str::slug($attribute->name);
            }
        });
    }

    public function values()
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attribute')
            ->withPivot(['is_visible', 'is_variation'])
            ->withTimestamps();
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    public function scopeForVariations($query)
    {
        return $query->where('is_variation', true);
    }

    public function getTypeLabel()
    {
        return match($this->type) {
            'select' => 'Dropdown Select',
            'color' => 'Color Swatch',
            'button' => 'Button',
            'radio' => 'Radio Buttons',
            default => 'Select',
        };
    }
}
