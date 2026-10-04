<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'avatar',
        'date_of_birth',
        'gender',
        'company',
        'website',
        'notes',
        'is_admin',
        'admin_role',
        'is_driver',
        'driver_phone',
        'driver_vehicle',
        'driver_license',
        'driver_status',
        'driver_rating',
        'driver_total_ratings',
        'driver_total_deliveries',
        'driver_total_earnings',
        'driver_commission_rate',
        'driver_availability_schedule',
        'driver_zones',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'admin_role' => 'string',
            'is_driver' => 'boolean',
            'is_active' => 'boolean',
            'date_of_birth' => 'date',
            'last_login_at' => 'datetime',
            'driver_rating' => 'decimal:2',
            'driver_total_earnings' => 'decimal:2',
            'driver_commission_rate' => 'decimal:2',
            'driver_availability_schedule' => 'array',
            'driver_zones' => 'array',
        ];
    }

    /**
     * Get the user's age
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute()
    {
        return $this->is_active 
            ? '<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Active</span>'
            : '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Inactive</span>';
    }

    /**
     * Get total spent amount
     */
    public function getTotalSpentAttribute()
    {
        return $this->orders()->where('payment_status', 'paid')->sum('total');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    public function isVendor(): bool
    {
        return $this->vendor()->exists();
    }

    public function isApprovedVendor(): bool
    {
        return $this->vendor && $this->vendor->isApproved();
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if user has a specific admin role
     */
    public function hasAdminRole(string|array $roles): bool
    {
        if (!$this->is_admin) {
            return false;
        }

        // Super admin has access to everything
        if ($this->admin_role === 'super_admin') {
            return true;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        return in_array($this->admin_role, $roles);
    }

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_admin && $this->admin_role === 'super_admin';
    }

    /**
     * Check if user is a manager
     */
    public function isManager(): bool
    {
        return $this->is_admin && in_array($this->admin_role, ['super_admin', 'manager']);
    }

    /**
     * Check if user is an editor
     */
    public function isEditor(): bool
    {
        return $this->is_admin && in_array($this->admin_role, ['super_admin', 'manager', 'editor']);
    }

    /**
     * Get admin role label
     */
    public function getAdminRoleLabelAttribute(): string
    {
        return match($this->admin_role) {
            'super_admin' => 'Super Admin',
            'manager' => 'Manager',
            'editor' => 'Editor',
            default => 'Customer',
        };
    }

    /**
     * Get admin role badge HTML
     */
    public function getAdminRoleBadgeAttribute(): string
    {
        return match($this->admin_role) {
            'super_admin' => '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded-full">Super Admin</span>',
            'manager' => '<span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">Manager</span>',
            'editor' => '<span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">Editor</span>',
            default => '<span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Customer</span>',
        };
    }

    /**
     * Get available admin roles
     */
    public static function getAdminRoles(): array
    {
        return [
            'super_admin' => 'Super Admin - Full access to all features',
            'manager' => 'Manager - Can manage orders, products, users (no settings)',
            'editor' => 'Editor - Can manage products and content only',
        ];
    }

    public function isDriver(): bool
    {
        return $this->is_driver;
    }

    /**
     * Get orders assigned to this driver
     */
    public function driverOrders()
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

    /**
     * Get driver status badge
     */
    public function getDriverStatusBadgeAttribute()
    {
        return match($this->driver_status) {
            'available' => '<span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Available</span>',
            'busy' => '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Busy</span>',
            'offline' => '<span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Offline</span>',
            default => '<span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Unknown</span>',
        };
    }

    /**
     * Get driver earnings
     */
    public function driverEarnings()
    {
        return $this->hasMany(DriverEarning::class, 'driver_id');
    }

    /**
     * Get driver notifications
     */
    public function driverNotifications()
    {
        return $this->hasMany(DriverNotification::class, 'driver_id');
    }

    /**
     * Get unread driver notifications count
     */
    public function getUnreadDriverNotificationsCountAttribute()
    {
        return $this->driverNotifications()->where('is_read', false)->count();
    }

    /**
     * Check if the user has verified their email address.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark the user's email as verified.
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Get the email verification tokens for the user.
     */
    public function emailVerificationTokens()
    {
        return $this->hasMany(EmailVerificationToken::class);
    }
}
