<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DigitalFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'file_path',
        'file_url',
        'file_type',
        'file_name',
        'file_extension',
        'file_size',
        'download_limit',
        'download_expiry_days',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_limit' => 'integer',
        'download_expiry_days' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function downloadLogs()
    {
        return $this->hasMany(DownloadLog::class);
    }

    /**
     * Get the download URL for this file
     */
    public function getDownloadUrlAttribute()
    {
        if ($this->file_type === 'url') {
            return $this->file_url;
        }
        
        return $this->file_path ? Storage::disk('local')->url($this->file_path) : null;
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file exists
     */
    public function fileExists(): bool
    {
        if ($this->file_type === 'url') {
            return !empty($this->file_url);
        }
        
        return $this->file_path && Storage::disk('local')->exists($this->file_path);
    }

    /**
     * Get the full file path for local files
     */
    public function getFullPathAttribute()
    {
        if ($this->file_type === 'upload' && $this->file_path) {
            return Storage::disk('local')->path($this->file_path);
        }
        return null;
    }
}
