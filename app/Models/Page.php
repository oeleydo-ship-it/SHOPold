<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasTranslations;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'featured_image',
        'template',
        'is_active',
        'show_in_menu',
        'menu_order',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_menu' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('menu_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInMenu($query)
    {
        return $query->where('show_in_menu', true)->orderBy('menu_order');
    }

    public function scopeParentPages($query)
    {
        return $query->whereNull('parent_id');
    }

    public function translations()
    {
        return $this->hasMany(PageTranslation::class);
    }

    /**
     * Get translated title
     */
    public function getTranslatedTitleAttribute(): string
    {
        return $this->getTranslatedAttribute('title') ?? $this->title;
    }

    /**
     * Get translated content
     */
    public function getTranslatedContentAttribute(): ?string
    {
        return $this->getTranslatedAttribute('content');
    }

    public function getUrlAttribute()
    {
        return route('page.show', $this->slug);
    }

    public function getFeaturedImageUrlAttribute()
    {
        return $this->featured_image ? asset('storage/' . $this->featured_image) : null;
    }

    /**
     * Available page templates
     */
    public static function getTemplates(): array
    {
        return [
            'default' => 'Default',
            'full-width' => 'Full Width',
            'sidebar-left' => 'Sidebar Left',
            'sidebar-right' => 'Sidebar Right',
            'landing' => 'Landing Page',
            'contact' => 'Contact Page',
        ];
    }
}
