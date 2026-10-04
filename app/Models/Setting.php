<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    protected static $cache = null;
    protected static $cacheKey = 'app_settings_v2';
    protected static $cacheTtl = 3600; // 1 hour (reduced from 24 hours for better consistency)

    /**
     * Get a setting value by key
     */
    /** Runtime overrides (not persisted), e.g. the active theme's customizer values. */
    protected static array $overlay = [];

    public static function overlay(array $values): void
    {
        static::$overlay = $values;
    }

    public static function get(string $key, $default = null)
    {
        if (array_key_exists($key, static::$overlay)) {
            return static::$overlay[$key];
        }

        $settings = static::getAllCached();
        
        if (!isset($settings[$key])) {
            return $default;
        }

        return static::castValue($settings[$key]['value'], $settings[$key]['type']);
    }

    /**
     * Get a setting value directly from database (bypassing cache)
     */
    public static function getFresh(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general'): bool
    {
        try {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
                $type = 'json';
            }

            // Use DB transaction for reliability
            DB::beginTransaction();
            
            static::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => $type, 'group' => $group]
            );
            
            DB::commit();
            
            // Clear cache after successful save
            static::clearCache();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to save setting {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set multiple settings at once (with transaction)
     */
    public static function setMany(array $settings, string $group = 'general'): bool
    {
        try {
            DB::beginTransaction();
            
            foreach ($settings as $key => $value) {
                $type = 'string';
                if (is_bool($value)) {
                    $type = 'boolean';
                    $value = $value ? '1' : '0';
                } elseif (is_int($value)) {
                    $type = 'integer';
                } elseif (is_float($value)) {
                    $type = 'float';
                } elseif (is_array($value)) {
                    $type = 'json';
                    $value = json_encode($value);
                }

                static::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'type' => $type, 'group' => $group]
                );
            }
            
            DB::commit();
            static::clearCache();
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to save multiple settings: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a setting exists
     */
    public static function has(string $key): bool
    {
        $settings = static::getAllCached();
        return isset($settings[$key]);
    }

    /**
     * Remove a setting
     */
    public static function forget(string $key): void
    {
        static::where('key', $key)->delete();
        static::clearCache();
    }

    /**
     * Get all settings for a specific group
     */
    public static function getGroup(string $group): array
    {
        $settings = static::getAllCached();
        $grouped = [];

        foreach ($settings as $key => $data) {
            if ($data['group'] === $group) {
                $grouped[$key] = static::castValue($data['value'], $data['type']);
            }
        }

        return $grouped;
    }

    /**
     * Get all settings from cache
     */
    public static function getAllCached(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        try {
            static::$cache = Cache::remember(static::$cacheKey, static::$cacheTtl, function () {
                return static::all()->keyBy('key')->map(function ($setting) {
                    return [
                        'value' => $setting->value,
                        'type' => $setting->type,
                        'group' => $setting->group,
                    ];
                })->toArray();
            });
        } catch (\Exception $e) {
            // If cache fails, load directly from database
            Log::warning("Cache failed, loading settings from database: " . $e->getMessage());
            static::$cache = static::all()->keyBy('key')->map(function ($setting) {
                return [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'group' => $setting->group,
                ];
            })->toArray();
        }

        return static::$cache;
    }

    /**
     * Clear the settings cache (comprehensive)
     */
    public static function clearCache(): void
    {
        try {
            // Clear Laravel cache
            Cache::forget(static::$cacheKey);
            
            // Also try to clear with tags if using tagged cache
            try {
                Cache::tags(['settings'])->flush();
            } catch (\Exception $e) {
                // Tags not supported, ignore
            }
            
            // Clear static cache
            static::$cache = null;
            
            // Clear config cache if possible (for production servers)
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            
        } catch (\Exception $e) {
            Log::warning("Failed to clear cache: " . $e->getMessage());
            // Still clear static cache even if Laravel cache fails
            static::$cache = null;
        }
    }

    /**
     * Force refresh all settings from database
     */
    public static function refreshAll(): array
    {
        static::clearCache();
        static::$cache = null;
        return static::getAllCached();
    }

    /**
     * Cast value to appropriate type
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Debug: Get cache status
     */
    public static function getCacheStatus(): array
    {
        return [
            'static_cache_loaded' => static::$cache !== null,
            'static_cache_count' => static::$cache ? count(static::$cache) : 0,
            'cache_key' => static::$cacheKey,
            'laravel_cache_exists' => Cache::has(static::$cacheKey),
            'database_count' => static::count(),
        ];
    }
}
