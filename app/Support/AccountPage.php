<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\PaymentGatewayService;

/** Props for the React customer account pages (Storefront/Account/*). */
class AccountPage
{
    public static function seo(string $title): array
    {
        return ['title' => $title, 'robots' => 'noindex'];
    }

    public static function dashboard($user, $recentOrders, int $orderCount, int $wishlistCount): array
    {
        return [
            'stats' => ['orders' => $orderCount, 'wishlist' => $wishlistCount],
            'recentOrders' => $recentOrders->map(fn (Order $o) => static::orderRow($o))->values(),
            'seo' => static::seo('My Account'),
        ];
    }

    public static function orders($orders): array
    {
        return [
            'orders' => $orders->through(fn (Order $o) => static::orderRow($o, true)),
            'seo' => static::seo('My Orders'),
        ];
    }

    /** One line of an order list. With $items, also the item thumbnails and the delivery progress. */
    public static function orderRow(Order $o, bool $items = false): array
    {
        $row = [
            'id' => $o->id,
            'number' => $o->order_number,
            'date' => $o->created_at?->format('M d, Y'),
            'time' => $o->created_at?->format('h:i A'),
            'status' => static::status($o->status),
            'payment' => static::paymentStatus($o->payment_status),
            'total' => format_currency($o->total),
            'paid' => $o->payment_status === 'paid',
            'url' => route('account.orders.show', $o),
        ];
        if ($items) {
            $physical = $o->items->reject(fn ($i) => static::isDigital($i));
            $row += [
                'itemCount' => $o->items->count(),
                'names' => $o->items->map(fn ($i) => $i->product?->name ?? $i->product_name)->filter()->implode(', '),
                'thumbs' => $o->items->take(3)->map(fn ($i) => $i->product?->image ? asset('storage/' . $i->product->image) : null)->values(),
                'digitalOnly' => $physical->isEmpty() && $o->items->isNotEmpty(),
                'delivered' => $physical->where('fulfillment_status', 'delivered')->count(),
                'physical' => $physical->count(),
            ];
        }

        return $row;
    }

    public static function orderDetail(Order $o): array
    {
        $payments = app(PaymentGatewayService::class);
        $method = $o->payment_method ?? 'cod';
        $pickup = $o->delivery_type === 'pickup';
        $steps = $pickup
            ? ['pending' => 'Placed', 'processing' => 'Processing', 'ready_for_pickup' => 'Ready for Pickup', 'delivered' => 'Picked Up']
            : ['pending' => 'Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
        $physical = $o->items->reject(fn ($i) => static::isDigital($i));
        $loc = $o->pickupLocation;

        return [
            'order' => [
                'id' => $o->id,
                'number' => $o->order_number,
                'date' => $o->created_at?->format('M d, Y h:i A'),
                'status' => static::status($o->status),
                'statusKey' => $o->status,
                'payment' => static::paymentStatus($o->payment_status),
                'paid' => $o->payment_status === 'paid',
                'pickup' => $pickup,
                'steps' => collect($steps)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
                'dates' => [
                    'placed' => $o->created_at?->format('M d'),
                    'processing' => in_array($o->status, ['processing', 'shipped', 'ready_for_pickup', 'delivered'], true) ? $o->updated_at?->format('M d') : null,
                    'shipped' => $o->shipped_at?->format('M d'),
                    'delivered' => $o->delivered_at?->format('M d'),
                ],
                'digitalOnly' => $physical->isEmpty() && $o->items->isNotEmpty(),
                'hasDigital' => $o->items->contains(fn ($i) => static::isDigital($i)),
                'fulfillment' => [
                    'physical' => $physical->count(),
                    'delivered' => $physical->where('fulfillment_status', 'delivered')->count(),
                    'shipped' => $physical->where('fulfillment_status', 'shipped')->count(),
                ],
                'items' => $o->items->map(fn (OrderItem $i) => [
                    'id' => $i->id,
                    'name' => $i->product_name,
                    'url' => $i->product?->slug ? route('shop.show', $i->product->slug) : null,
                    'image' => $i->variation?->image ? $i->variation->image_url : ($i->product?->image ? $i->product->image_url : null),
                    'options' => collect(is_array($i->variation_options) ? $i->variation_options : [])
                        ->map(fn ($v, $k) => ['name' => (string) $k, 'value' => (string) $v])->values(),
                    'quantity' => (int) $i->quantity,
                    'price' => format_currency($i->price),
                    'total' => format_currency($i->total),
                    'digital' => static::isDigital($i),
                    'fulfillment' => ['label' => $i->fulfillment_status_label, 'key' => $i->fulfillment_status],
                    'tracking' => $i->tracking_number ? trim(($i->shipping_carrier ?? 'Tracking') . ': ' . $i->tracking_number) : null,
                    'shippedAt' => $i->shipped_at?->format('M d, Y h:i A'),
                    'deliveredAt' => $i->delivered_at?->format('M d, Y h:i A'),
                ])->values(),
                'address' => $pickup ? null : [
                    'name' => $o->shipping_name,
                    'lines' => array_values(array_filter([
                        $o->shipping_address,
                        trim("{$o->shipping_city}, {$o->shipping_state} {$o->shipping_zip}", ' ,'),
                        $o->shipping_country,
                    ])),
                    'phone' => $o->shipping_phone,
                ],
                'pickupLocation' => $pickup ? ($loc ? [
                    'name' => $loc->name,
                    'lines' => array_values(array_filter([$loc->address, trim("{$loc->city}, {$loc->state} {$loc->zip}", ' ,'), $loc->country])),
                    'phone' => $loc->phone,
                    'instructions' => $loc->instructions,
                ] : ['name' => null, 'lines' => array_filter([$o->shipping_address]), 'phone' => null, 'instructions' => null]) : null,
                'totals' => [
                    'subtotal' => format_currency($o->subtotal),
                    'discount' => $o->discount > 0 ? format_currency($o->discount) : null,
                    'shipping' => format_currency($o->shipping_cost),
                    'tax' => format_currency($o->tax),
                    'total' => format_currency($o->total),
                ],
                'paymentMethod' => ['name' => $payments->getPaymentMethodName($method), 'card' => $payments->isCardPayment($method)],
                'refund' => $o->payment_status === 'refunded' ? [
                    'date' => $o->refunded_at?->format('M d, Y'),
                    'amount' => $o->refund_amount ? format_currency($o->refund_amount) : null,
                ] : null,
                'driver' => $o->driver && $o->status === 'delivered' && Modules::enabled('driver_portal') ? [
                    'name' => $o->driver->name,
                    'rating' => $o->driver_rating ? (int) $o->driver_rating : null,
                    'comment' => $o->driver_rating_comment,
                    'tip' => $o->driver_tip > 0 ? format_currency($o->driver_tip) : null,
                    'tipOptions' => collect([2, 5, 10, 20])->map(fn ($v) => ['value' => $v, 'label' => format_currency($v)]),
                ] : null,
                'links' => [
                    'invoice' => $o->payment_status === 'paid' ? route('account.orders.invoice', $o) : null,
                    'downloads' => $o->items->contains(fn ($i) => (bool) $i->is_digital) && Modules::enabled('digital_products')
                        ? route('account.orders.downloads', $o) : null,
                ],
            ],
            'seo' => static::seo('Order ' . $o->order_number),
        ];
    }

    public static function profile($user): array
    {
        $old = fn ($k, $v) => old($k, $v) ?? '';

        return [
            'profile' => [
                'name' => $old('name', $user->name),
                'email' => $old('email', $user->email),
                'phone' => $old('phone', $user->phone),
                'date_of_birth' => $old('date_of_birth', $user->date_of_birth?->format('Y-m-d')),
                'gender' => $old('gender', $user->gender),
                'company' => $old('company', $user->company),
                'address' => $old('address', $user->address),
                'city' => $old('city', $user->city),
                'state' => $old('state', $user->state),
                'zip_code' => $old('zip_code', $user->zip_code),
                'country' => $old('country', $user->country),
            ],
            'seo' => static::seo('Profile'),
        ];
    }

    public static function wishlist($wishlist): array
    {
        $ids = $wishlist->getCollection()->pluck('product_id');
        $cards = ProductCards::with(Product::query())->whereIn('id', $ids)->get()->keyBy('id');

        return [
            'items' => $wishlist->through(fn ($w) => $cards->has($w->product_id) ? ProductCards::card($cards[$w->product_id]) : null),
            'seo' => static::seo('Wishlist'),
        ];
    }

    public static function isDigital($item): bool
    {
        return (bool) $item->is_digital || ($item->product && $item->product->product_type === 'digital');
    }

    /** Order status as a label and a colour name the page maps to classes (instead of the admin badge HTML). */
    public static function status(?string $s): array
    {
        return [
            'label' => ['pending' => 'Pending', 'processing' => 'Processing', 'ready_for_pickup' => 'Ready for Pickup', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'][$s] ?? 'Unknown',
            'tone' => ['pending' => 'amber', 'processing' => 'blue', 'ready_for_pickup' => 'purple', 'shipped' => 'indigo', 'delivered' => 'green', 'cancelled' => 'red'][$s] ?? 'gray',
        ];
    }

    public static function paymentStatus(?string $s): array
    {
        return [
            'label' => ['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'][$s] ?? 'Unknown',
            'tone' => ['pending' => 'amber', 'paid' => 'green', 'failed' => 'red', 'refunded' => 'blue'][$s] ?? 'gray',
        ];
    }
}
