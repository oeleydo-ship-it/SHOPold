<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Get translation for current locale
     */
    public function translation(?string $locale = null)
    {
        $locale = $locale ?? App::getLocale();
        return $this->translations()->where('locale', $locale)->first();
    }

    /**
     * Get translated attribute
     */
    public function getTranslatedAttribute(string $attribute, ?string $locale = null): ?string
    {
        $locale = $locale ?? App::getLocale();
        $defaultLocale = setting('store.default_language', config('app.fallback_locale', 'en'));

        // If current locale is default, return original attribute
        if ($locale === $defaultLocale) {
            return $this->{$attribute};
        }

        // Try to get translation for current locale
        // Use eager-loaded translations if available, otherwise query
        $translation = null;
        if ($this->relationLoaded('translations')) {
            $translation = $this->translations->where('locale', $locale)->first();
        } else {
            $translation = $this->translations()->where('locale', $locale)->first();
        }
        
        if ($translation && !empty($translation->{$attribute})) {
            return $translation->{$attribute};
        }

        // Fallback to original attribute
        return $this->{$attribute};
    }

    /**
     * Get translated name
     */
    public function getTranslatedNameAttribute(): string
    {
        return $this->getTranslatedAttribute('name') ?? $this->name;
    }

    /**
     * Get translated description
     */
    public function getTranslatedDescriptionAttribute(): ?string
    {
        return $this->getTranslatedAttribute('description');
    }

    /**
     * Get translated short description
     */
    public function getTranslatedShortDescriptionAttribute(): ?string
    {
        return $this->getTranslatedAttribute('short_description');
    }

    /**
     * Save translation for a locale
     */
    public function saveTranslation(string $locale, array $data): void
    {
        $this->translations()->updateOrCreate(
            ['locale' => $locale],
            $data
        );
    }

    /**
     * Get all translations as array
     */
    public function getAllTranslations(): array
    {
        return $this->translations->keyBy('locale')->toArray();
    }

    /**
     * Check if translation exists for locale
     */
    public function hasTranslation(string $locale): bool
    {
        return $this->translations()->where('locale', $locale)->exists();
    }

    /**
     * Delete translation for locale
     */
    public function deleteTranslation(string $locale): bool
    {
        return $this->translations()->where('locale', $locale)->delete() > 0;
    }
}
