<?php

namespace App\Support;

use App\Mail\{DriverAssigned, VendorNewOrder, VendorPayoutUpdated, VendorStatusChanged};
use App\Models\{Order, User, Vendor, VendorPayout};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Emails to vendors and drivers. Each one has a switch in Settings > Email > Email Notifications.
 * Never throws: a mail problem must not block the order, approval, payout or assignment that triggered it.
 */
class PartnerMailer
{
    /** One email per vendor with items in the order. */
    public static function vendorNewOrder(Order $order): void
    {
        if (!setting('email.vendor_new_order', true) || !Modules::enabled('multi_vendor')) {
            return;
        }

        $vendorIds = $order->items()->with('product:id,vendor_id')->get()->pluck('product.vendor_id')->filter()->unique();
        foreach (Vendor::whereIn('id', $vendorIds)->where('status', 'approved')->get() as $vendor) {
            static::send($vendor->email ?: $vendor->user?->email, fn () => new VendorNewOrder($order, $vendor), "vendor {$vendor->id} new order {$order->order_number}");
        }
    }

    public static function vendorStatusChanged(Vendor $vendor, string $previous): void
    {
        if ($vendor->status === $previous || !in_array($vendor->status, ['approved', 'rejected', 'suspended'], true) || !setting('email.vendor_status', true)) {
            return;
        }
        static::send($vendor->user?->email ?: $vendor->email, fn () => new VendorStatusChanged($vendor), "vendor {$vendor->id} status {$vendor->status}");
    }

    public static function payoutUpdated(VendorPayout $payout, string $previous): void
    {
        if ($payout->status === $previous || !in_array($payout->status, ['completed', 'failed'], true) || !setting('email.vendor_payout', true)) {
            return;
        }
        $vendor = $payout->vendor;
        static::send($vendor?->email ?: $vendor?->user?->email, fn () => new VendorPayoutUpdated($payout), "payout {$payout->id} {$payout->status}");
    }

    public static function driverAssigned(Order $order, User $driver): void
    {
        if (!setting('email.driver_assigned', true)) {
            return;
        }
        static::send($driver->email, fn () => new DriverAssigned($order, $driver), "driver {$driver->id} order {$order->order_number}");
    }

    protected static function send(?string $to, \Closure $mail, string $what): void
    {
        if (!$to) {
            return;
        }
        try {
            Mail::to($to)->send($mail());
        } catch (\Throwable $e) {
            Log::error("Email not sent ({$what}): " . $e->getMessage());
        }
    }
}
