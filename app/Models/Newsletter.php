<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    protected $fillable = [
        'email',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * Scope for active subscribers
     */
    public function scopeActive($query)
    {
        return $query->whereNull('unsubscribed_at');
    }

    /**
     * Check if subscriber is active
     */
    public function isActive(): bool
    {
        return is_null($this->unsubscribed_at);
    }

    /**
     * Unsubscribe
     */
    public function unsubscribe(): void
    {
        $this->update(['unsubscribed_at' => now()]);
    }

    /**
     * Resubscribe
     */
    public function resubscribe(): void
    {
        $this->update([
            'unsubscribed_at' => null,
            'subscribed_at' => now(),
        ]);
    }
}
