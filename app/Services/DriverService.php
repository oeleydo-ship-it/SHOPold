<?php

namespace App\Services;

use App\Models\DriverEarning;
use App\Models\DriverNotification;
use App\Models\Order;
use App\Models\User;

class DriverService
{
    /**
     * Assign a driver to an order
     */
    public function assignDriver(Order $order, User $driver): void
    {
        $order->update([
            'driver_id' => $driver->id,
            'driver_assigned_at' => now(),
        ]);

        // Update driver status to busy
        $driver->update(['driver_status' => 'busy']);

        // Send notification to driver
        $this->notifyDriver($driver, $order, 'new_order', 
            'New Delivery Assigned',
            "Order #{$order->order_number} has been assigned to you. Pickup from store and deliver to {$order->shipping_city}."
        );
    }

    /**
     * Unassign driver from order
     */
    public function unassignDriver(Order $order): void
    {
        $driver = $order->driver;
        
        $order->update([
            'driver_id' => null,
            'driver_assigned_at' => null,
        ]);

        if ($driver) {
            // Check if driver has other active orders
            $activeOrders = Order::where('driver_id', $driver->id)
                ->whereIn('status', ['processing', 'shipped'])
                ->count();

            if ($activeOrders === 0) {
                $driver->update(['driver_status' => 'available']);
            }

            $this->notifyDriver($driver, $order, 'order_cancelled',
                'Order Unassigned',
                "Order #{$order->order_number} has been unassigned from you."
            );
        }
    }

    /**
     * Record delivery completion and calculate earnings
     */
    public function completeDelivery(Order $order): void
    {
        $driver = $order->driver;
        if (!$driver) return;

        $commissionRate = $driver->driver_commission_rate ?? 10;
        $deliveryEarning = $order->shipping_cost * ($commissionRate / 100);

        // Create earning record for delivery
        DriverEarning::create([
            'driver_id' => $driver->id,
            'order_id' => $order->id,
            'type' => 'delivery',
            'amount' => $deliveryEarning,
            'description' => "Delivery commission for order {$order->order_number}",
            'status' => 'completed',
        ]);

        // Update driver stats
        $driver->increment('driver_total_deliveries');
        $driver->increment('driver_total_earnings', $deliveryEarning);

        // Update order earnings
        $order->update(['driver_earnings' => $deliveryEarning]);

        // Check if driver has other active orders
        $activeOrders = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['processing', 'shipped'])
            ->where('id', '!=', $order->id)
            ->count();

        if ($activeOrders === 0) {
            $driver->update(['driver_status' => 'available']);
        }
    }

    /**
     * Add tip to driver
     */
    public function addTip(Order $order, float $amount): void
    {
        $driver = $order->driver;
        if (!$driver || $amount <= 0) return;

        DriverEarning::create([
            'driver_id' => $driver->id,
            'order_id' => $order->id,
            'type' => 'tip',
            'amount' => $amount,
            'description' => "Tip for order {$order->order_number}",
            'status' => 'completed',
        ]);

        $order->increment('driver_tip', $amount);
        $order->increment('driver_earnings', $amount);
        $driver->increment('driver_total_earnings', $amount);

        $this->notifyDriver($driver, $order, 'tip',
            'You received a tip!',
            "You received a " . format_currency($amount) . " tip for order #{$order->order_number}."
        );
    }

    /**
     * Rate driver for an order
     */
    public function rateDriver(Order $order, int $rating, ?string $comment = null): void
    {
        $driver = $order->driver;
        if (!$driver) return;

        $order->update([
            'driver_rating' => $rating,
            'driver_rating_comment' => $comment,
        ]);

        // Recalculate driver's average rating
        $totalRatings = $driver->driver_total_ratings + 1;
        $currentTotal = $driver->driver_rating * $driver->driver_total_ratings;
        $newAverage = ($currentTotal + $rating) / $totalRatings;

        $driver->update([
            'driver_rating' => round($newAverage, 2),
            'driver_total_ratings' => $totalRatings,
        ]);

        $this->notifyDriver($driver, $order, 'rating',
            'New Rating Received',
            "You received a {$rating}-star rating for order #{$order->order_number}."
        );
    }

    /**
     * Get available drivers
     */
    public function getAvailableDrivers()
    {
        return User::where('is_driver', true)
            ->where('is_active', true)
            ->where('driver_status', 'available')
            ->orderByDesc('driver_rating')
            ->get();
    }

    /**
     * Send notification to driver
     */
    public function notifyDriver(User $driver, ?Order $order, string $type, string $title, string $message): void
    {
        DriverNotification::create([
            'driver_id' => $driver->id,
            'order_id' => $order?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    /**
     * Add bonus to driver
     */
    public function addBonus(User $driver, float $amount, string $description): void
    {
        DriverEarning::create([
            'driver_id' => $driver->id,
            'type' => 'bonus',
            'amount' => $amount,
            'description' => $description,
            'status' => 'completed',
        ]);

        $driver->increment('driver_total_earnings', $amount);

        $this->notifyDriver($driver, null, 'bonus',
            'Bonus Received!',
            "You received a " . format_currency($amount) . " bonus: {$description}"
        );
    }
}
