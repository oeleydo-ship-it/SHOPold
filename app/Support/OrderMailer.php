<?php

namespace App\Support;

use App\Mail\{OrderDelivered, OrderReadyForPickup, OrderShipped, OrderStatusUpdated};
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The customer email for an order status change, whoever changed it (admin, driver or vendor fulfilment).
 * Honours the switches in Settings > Email > Email Notifications. Never throws: a mail problem must not undo
 * or block the status change itself.
 */
class OrderMailer
{
    public static function statusChanged(Order $order, string $previousStatus): void
    {
        $status = (string) $order->status;
        if ($status === $previousStatus) {
            return;
        }

        [$toggle, $mail] = match ($status) {
            'shipped' => ['email.order_shipped', fn () => new OrderShipped($order)],
            'delivered' => ['email.order_delivered', fn () => new OrderDelivered($order)],
            'ready_for_pickup' => ['email.order_status_updates', fn () => new OrderReadyForPickup($order)],
            default => ['email.order_status_updates', fn () => new OrderStatusUpdated($order, $previousStatus)],
        };

        if (!setting($toggle, true)) {
            return;
        }

        try {
            $to = $order->user?->email ?: $order->shipping_email;
            if ($to) {
                Mail::to($to)->send($mail());
            }
        } catch (\Throwable $e) {
            Log::error("Order {$order->order_number}: '{$status}' email not sent: " . $e->getMessage());
        }
    }
}
